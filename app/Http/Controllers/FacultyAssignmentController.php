<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FacultyAssignmentController extends Controller
{
    public function index(): View
    {
        $this->ensureAcademicStaff();

        $assignments = SubjectAssignment::query()
            ->with(['faculty.user', 'subject.program', 'subject.semester', 'classSection'])
            ->withCount(['timetables', 'lectureSessions'])
            ->orderByDesc('academic_year')
            ->get();

        $facultyGroups = $assignments
            ->groupBy('faculty_id')
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'faculty' => $first->faculty,
                    'assignments' => $rows->sortBy(fn (SubjectAssignment $a) => $a->subject->subject_name)->values(),
                    'subject_count' => $rows->pluck('subject_id')->unique()->count(),
                ];
            })
            ->sortBy(fn (array $group) => $group['faculty']->user->name)
            ->values();

        return view('assignments.index', [
            'facultyGroups' => $facultyGroups,
            'assignmentCount' => $assignments->count(),
        ]);
    }

    public function create(): View
    {
        $this->ensureAcademicStaff();

        return view('assignments.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAcademicStaff();

        $subjectIds = $request->input('subject_ids', []);
        if (is_array($subjectIds) && count($subjectIds) > 0) {
            return $this->storeMultiple($request);
        }

        $validated = $this->validateAssignment($request);
        SubjectAssignment::create($validated);

        return redirect()->route('assignments.index')->with('status', 'Faculty assignment created.');
    }

    public function edit(SubjectAssignment $assignment): View
    {
        $this->ensureAcademicStaff();

        $assignment->load(['faculty.user', 'subject', 'classSection']);

        return view('assignments.edit', [
            'assignment' => $assignment,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, SubjectAssignment $assignment): RedirectResponse
    {
        $this->ensureAcademicStaff();

        $assignment->update($this->validateAssignment($request, $assignment->id));

        return redirect()->route('assignments.index')->with('status', 'Faculty assignment updated.');
    }

    public function destroy(SubjectAssignment $assignment): RedirectResponse
    {
        $this->ensureAcademicStaff();

        if ($assignment->timetables()->exists() || $assignment->lectureSessions()->exists()) {
            $assignment->update(['status' => 'inactive']);

            return redirect()
                ->route('assignments.index')
                ->with('status', 'This assignment is already used, so it was set inactive instead of deleted.');
        }

        $assignment->delete();

        return redirect()->route('assignments.index')->with('status', 'Faculty assignment removed.');
    }

    public function status(Request $request, SubjectAssignment $assignment): RedirectResponse
    {
        $this->ensureAcademicStaff();

        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $assignment->update($validated);

        return redirect()
            ->route('assignments.index')
            ->with('status', 'Faculty assignment marked '.$validated['status'].'.');
    }

    private function storeMultiple(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'faculty_id' => ['required', 'exists:faculty,id'],
            'class_section_id' => ['required', 'exists:class_sections,id'],
            'academic_year' => ['required', 'string', 'max:20'],
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $created = 0;
        $skipped = 0;

        foreach ($validated['subject_ids'] as $subjectId) {
            $payload = [
                'faculty_id' => $validated['faculty_id'],
                'subject_id' => $subjectId,
                'class_section_id' => $validated['class_section_id'],
                'academic_year' => $validated['academic_year'],
                'status' => $validated['status'],
            ];

            $exists = SubjectAssignment::query()
                ->where('faculty_id', $payload['faculty_id'])
                ->where('subject_id', $payload['subject_id'])
                ->where('class_section_id', $payload['class_section_id'])
                ->where('academic_year', $payload['academic_year'])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            SubjectAssignment::create($payload);
            $created++;
        }

        if ($created === 0) {
            throw ValidationException::withMessages([
                'subject_ids' => 'All selected subjects are already assigned to this faculty for that class and year.',
            ]);
        }

        $message = $created === 1
            ? '1 subject assigned to faculty.'
            : "{$created} subjects assigned to faculty.";

        if ($skipped > 0) {
            $message .= " {$skipped} already existed and were skipped.";
        }

        return redirect()->route('assignments.index')->with('status', $message);
    }

    private function ensureAcademicStaff(): void
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isHod(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $classSections = ClassSection::with(['program', 'semester'])
            ->where('status', 'active')
            ->orderBy('display_name')
            ->get();

        $subjects = Subject::with(['program', 'semester'])
            ->where('status', 'active')
            ->orderBy('subject_name')
            ->get();

        $existingAssignments = SubjectAssignment::query()
            ->get(['faculty_id', 'subject_id', 'class_section_id', 'academic_year'])
            ->map(fn (SubjectAssignment $a) => "{$a->faculty_id}-{$a->subject_id}-{$a->class_section_id}-{$a->academic_year}")
            ->flip();

        return [
            'faculty' => Faculty::with('user')->where('status', 'active')->get()->sortBy('user.name'),
            'subjects' => $subjects,
            'classSections' => $classSections,
            'existingAssignmentKeys' => $existingAssignments->keys()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAssignment(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'faculty_id' => ['required', 'exists:faculty,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'class_section_id' => ['required', 'exists:class_sections,id'],
            'academic_year' => ['required', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if ($this->assignmentExists($validated, $ignoreId)) {
            throw ValidationException::withMessages([
                'subject_id' => 'This faculty is already assigned to that subject for this class and academic year.',
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assignmentExists(array $validated, ?int $ignoreId = null): bool
    {
        return SubjectAssignment::query()
            ->where('faculty_id', $validated['faculty_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('class_section_id', $validated['class_section_id'])
            ->where('academic_year', $validated['academic_year'])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
