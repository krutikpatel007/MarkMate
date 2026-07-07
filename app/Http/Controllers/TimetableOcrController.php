<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\SubjectAssignment;
use App\Models\Timetable;
use App\Support\TimetablePeriods;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TimetableOcrController extends Controller
{
    private const DAY_NAMES = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    public function showUpload(): View
    {
        $this->ensureManageable();

        $authorizedDeptIds = $this->getAuthorizedDepartmentIds();
        $sections = ClassSection::query()
            ->with(['program', 'semester'])
            ->whereHas('program', function ($query) use ($authorizedDeptIds) {
                $query->whereIn('department_id', $authorizedDeptIds);
            })
            ->orderBy('display_name')
            ->get();

        return view('timetables.upload_ocr', compact('sections'));
    }

    public function processOcr(Request $request): View|RedirectResponse
    {
        $this->ensureManageable();

        $request->validate([
            'class_section_id' => ['required', 'exists:class_sections,id'],
            'timetable_image' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $sectionId = $request->integer('class_section_id');
        $section = ClassSection::with('program.department')->findOrFail($sectionId);

        $authorizedDeptIds = $this->getAuthorizedDepartmentIds();
        abort_unless(in_array($section->program->department_id, $authorizedDeptIds), 403);

        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            return back()->withErrors(['timetable_image' => 'Gemini API Key is not configured in the server .env. Please set GEMINI_API_KEY.']);
        }

        try {
            $imageFile = $request->file('timetable_image');
            $mimeType = $imageFile->getMimeType();
            $base64Data = base64_encode(file_get_contents($imageFile->getRealPath()));

            $prompt = $this->buildOcrPrompt();

            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inlineData' => [
                                    'mimeType' => $mimeType,
                                    'data' => $base64Data,
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if (! $response->successful()) {
                return back()->withErrors(['timetable_image' => 'API Error: ' . ($response->json('error.message') ?: $response->body())]);
            }

            $rawText = $response->json('candidates.0.content.parts.0.text') ?? '';
            $extractedSlots = json_decode($rawText, true);

            if (! is_array($extractedSlots)) {
                return back()->withErrors(['timetable_image' => 'The AI response did not contain a valid JSON array. Please ensure the image is clear and try again.']);
            }

            $assignments = SubjectAssignment::query()
                ->where('class_section_id', $section->id)
                ->where('status', 'active')
                ->with(['subject', 'faculty.user'])
                ->get();

            $mappedSlots = [];
            foreach ($extractedSlots as $slot) {
                $dayOfWeek = (int) ($slot['day_of_week'] ?? 1);
                $startTime = trim($slot['start_time'] ?? '');
                $endTime = trim($slot['end_time'] ?? '');
                $subjectCode = trim($slot['subject_code'] ?? '');
                $facultyInitials = trim($slot['faculty_initials'] ?? '');
                $slotType = strtolower(trim($slot['slot_type'] ?? 'regular'));
                $lectureNo = (int) ($slot['lecture_no'] ?? 1);
                $cellLabel = trim($slot['cell_label'] ?? '');

                if (strlen($startTime) === 5) {
                    $startTime .= ':00';
                }
                if (strlen($endTime) === 5) {
                    $endTime .= ':00';
                }

                $matchedAssignmentId = $this->findMatchingAssignment($assignments, $subjectCode, $facultyInitials);

                $mappedSlots[] = [
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'subject_code' => $subjectCode,
                    'faculty_initials' => $facultyInitials,
                    'slot_type' => $slotType,
                    'lecture_no' => $lectureNo,
                    'cell_label' => $cellLabel,
                    'subject_assignment_id' => $matchedAssignmentId,
                ];
            }

            return view('timetables.preview_ocr', [
                'section' => $section,
                'assignments' => $assignments,
                'slots' => $mappedSlots,
                'dayNames' => self::DAY_NAMES,
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['timetable_image' => 'An error occurred while processing the timetable image: ' . $e->getMessage()]);
        }
    }

    public function saveOcr(Request $request): RedirectResponse
    {
        $this->ensureManageable();

        $request->validate([
            'class_section_id' => ['required', 'exists:class_sections,id'],
            'slots' => ['required', 'array'],
            'slots.*.day_of_week' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5, 6, 7])],
            'slots.*.subject_assignment_id' => ['nullable', 'exists:subject_assignments,id'],
            'slots.*.slot_type' => ['required', Rule::in(['regular', 'lab'])],
            'slots.*.start_time' => ['required', 'date_format:H:i:s'],
            'slots.*.end_time' => ['required', 'date_format:H:i:s'],
            'slots.*.lecture_no' => ['required', 'integer', 'min:1', 'max:20'],
            'slots.*.cell_label' => ['nullable', 'string', 'max:64'],
        ]);

        $sectionId = $request->integer('class_section_id');
        $section = ClassSection::with('program')->findOrFail($sectionId);

        $authorizedDeptIds = $this->getAuthorizedDepartmentIds();
        abort_unless(in_array($section->program->department_id, $authorizedDeptIds), 403);

        $submittedSlots = $request->input('slots');

        DB::transaction(function () use ($submittedSlots, $sectionId) {
            foreach ($submittedSlots as $index => $slotData) {
                if (empty($slotData['enabled']) || empty($slotData['subject_assignment_id'])) {
                    continue; // Skip unchecked or unmapped slots
                }

                $assignment = SubjectAssignment::query()->findOrFail($slotData['subject_assignment_id']);
                if ($assignment->class_section_id !== $sectionId) {
                    throw ValidationException::withMessages([
                        "slots.{$index}.subject_assignment_id" => 'Subject assignment does not belong to this class section.',
                    ]);
                }

                if (strtotime($slotData['start_time']) >= strtotime($slotData['end_time'])) {
                    throw ValidationException::withMessages([
                        "slots.{$index}.end_time" => 'End time must be after start time.',
                    ]);
                }

                // Check overlap with existing active slots for this section
                $assignmentIds = SubjectAssignment::query()
                    ->where('class_section_id', $sectionId)
                    ->pluck('id');

                $conflict = Timetable::query()
                    ->whereIn('subject_assignment_id', $assignmentIds)
                    ->where('day_of_week', $slotData['day_of_week'])
                    ->where('status', 'active')
                    ->get()
                    ->first(fn (Timetable $slot) => TimetablePeriods::timesOverlap(
                        $slotData['start_time'],
                        $slotData['end_time'],
                        $slot->start_time,
                        $slot->end_time
                    ));

                if ($conflict) {
                    throw ValidationException::withMessages([
                        "slots.{$index}.start_time" => 'Slot overlaps another existing active slot on the same day.',
                    ]);
                }

                // Create the slot
                Timetable::create([
                    'subject_assignment_id' => $slotData['subject_assignment_id'],
                    'day_of_week' => $slotData['day_of_week'],
                    'slot_type' => $slotData['slot_type'],
                    'start_time' => $slotData['start_time'],
                    'end_time' => $slotData['end_time'],
                    'lecture_no' => $slotData['lecture_no'],
                    'cell_label' => $slotData['cell_label'] ?? null,
                    'status' => 'active',
                ]);
            }
        });

        return redirect()
            ->route('timetables.index', ['class_section_id' => $sectionId])
            ->with('status', 'Timetable slots successfully imported from image.');
    }

    private function findMatchingAssignment($assignments, string $subjectCode, string $facultyInitials): ?int
    {
        if (empty($subjectCode)) {
            return null;
        }

        $subCodeNorm = strtolower(str_replace([' ', '-', '_'], '', $subjectCode));
        $facInitNorm = strtolower(str_replace([' ', '-', '_'], '', $facultyInitials));

        // Attempt 1: Exact subject code and faculty initials match
        if (! empty($facultyInitials)) {
            foreach ($assignments as $assignment) {
                $asgSubCode = strtolower(str_replace([' ', '-', '_'], '', $assignment->subject->subject_code ?? ''));
                $asgFacInit = strtolower(str_replace([' ', '-', '_'], '', $assignment->faculty->display_initials ?? ''));

                if ($asgSubCode === $subCodeNorm && $asgFacInit === $facInitNorm) {
                    return $assignment->id;
                }
            }
        }

        // Attempt 2: Subject code match and partial faculty initials / name match
        if (! empty($facultyInitials)) {
            foreach ($assignments as $assignment) {
                $asgSubCode = strtolower(str_replace([' ', '-', '_'], '', $assignment->subject->subject_code ?? ''));
                $asgFacInit = strtolower(str_replace([' ', '-', '_'], '', $assignment->faculty->display_initials ?? ''));
                $asgFacName = strtolower(str_replace(' ', '', $assignment->faculty->user->name ?? ''));

                if ($asgSubCode === $subCodeNorm && (str_contains($asgFacInit, $facInitNorm) || str_contains($asgFacName, $facInitNorm))) {
                    return $assignment->id;
                }
            }
        }

        // Attempt 3: Subject code match only
        foreach ($assignments as $assignment) {
            $asgSubCode = strtolower(str_replace([' ', '-', '_'], '', $assignment->subject->subject_code ?? ''));
            $asgSubName = strtolower(str_replace([' ', '-', '_'], '', $assignment->subject->subject_name ?? ''));

            if ($asgSubCode === $subCodeNorm || str_contains($asgSubName, $subCodeNorm)) {
                return $assignment->id;
            }
        }

        return null;
    }

    private function buildOcrPrompt(): string
    {
        return <<<PROMPT
You are a high-accuracy university timetable OCR and data extractor.
Analyze the uploaded image representing a class schedule grid.
Extract all teaching/lecture/lab slots from the grid.

For each slot in the timetable, extract the following:
1. day_of_week: An integer representing the day of the week (1 = Monday, 2 = Tuesday, 3 = Wednesday, 4 = Thursday, 5 = Friday, 6 = Saturday, 7 = Sunday).
2. start_time: The start time of the lecture in 24-hour HH:MM format (e.g., 09:15).
3. end_time: The end time of the lecture in 24-hour HH:MM format (e.g., 10:15).
4. subject_code: The subject code/name listed in the slot (e.g., "BCA301", "BCA301 Lab", "Java").
5. faculty_initials: The short initials or initials of the faculty member listed in the slot (e.g., "KS", "AD", "Prof. Krutik").
6. slot_type: Either "regular" or "lab" (set to "lab" if the text contains "lab", "practical", "prac", or if the duration of the slot spans multiple consecutive periods).
7. lecture_no: An integer representing the period slot number on that day (1, 2, 3, etc.).
8. cell_label: Any secondary text in the slot cell representing room numbers, classrooms, or details (e.g., "Room 101", "Lab 2").

Ensure that break periods (like LUNCH BREAK or short breaks) are NOT extracted as slots.
Only output the extracted slots.

Respond ONLY with a valid JSON array of objects representing the extracted slots. Do NOT include markdown code block wraps (like ```json ... ```), no preambles, and no postscripts.
PROMPT;
    }

    private function ensureManageable(): void
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isHod(), 403);
    }

    private function getAuthorizedDepartmentIds(): array
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return \App\Models\Department::pluck('id')->toArray();
        }
        if ($user->isHod()) {
            return [$user->facultyProfile->department_id];
        }
        return [];
    }
}
