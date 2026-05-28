<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\InternalMark;
use App\Models\InternalMarkComponent;
use App\Models\InternalMarkValue;
use App\Models\SubjectAssignment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InternalMarkController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isHod() || $user->isFaculty(), 403);

        $query = SubjectAssignment::query()
            ->with(['subject', 'classSection.program', 'faculty.user', 'internalMarkComponents'])
            ->where('status', 'active');

        if ($user->isFaculty()) {
            $query->where('faculty_id', $user->facultyProfile?->id);
        } elseif ($user->isHod()) {
            $query->whereHas('classSection.program', function ($q) {
                $q->whereIn('department_id', $this->manageableDepartmentIds());
            });
        }

        $assignments = $query->get()->map(function ($assignment) {
            $hasSubmitted = InternalMark::where('subject_assignment_id', $assignment->id)
                ->where('status', 'submitted')
                ->exists();

            $assignment->config_status = $assignment->internalMarkComponents->isEmpty()
                ? 'unconfigured'
                : ($hasSubmitted ? 'submitted' : 'draft');

            return $assignment;
        });

        return view('marks.index', compact('assignments'));
    }

    public function configureCreate(SubjectAssignment $subjectAssignment): View
    {
        $this->authorizeAssignmentAccess($subjectAssignment);
        $this->ensureNotSubmitted($subjectAssignment);

        $components = InternalMarkComponent::where('subject_assignment_id', $subjectAssignment->id)->get();

        return view('marks.configure', compact('subjectAssignment', 'components'));
    }

    public function configureStore(Request $request, SubjectAssignment $subjectAssignment): RedirectResponse
    {
        $this->authorizeAssignmentAccess($subjectAssignment);
        $this->ensureNotSubmitted($subjectAssignment);

        $validated = $request->validate([
            'components' => ['required', 'array', 'min:1'],
            'components.*.name' => ['required', 'string', 'max:255'],
            'components.*.max_marks' => ['required', 'numeric', 'min:1', 'max:30'],
        ]);

        $sum = 0;
        $seenNames = [];
        foreach ($validated['components'] as $comp) {
            $sum += (float) $comp['max_marks'];
            $nameKey = strtolower($comp['name']);
            if (isset($seenNames[$nameKey])) {
                throw ValidationException::withMessages([
                    'components' => ['Component names must be unique.'],
                ]);
            }
            $seenNames[$nameKey] = true;
        }

        if (abs($sum - 30.0) > 0.001) {
            throw ValidationException::withMessages([
                'components' => ['The sum of max marks of all components must be exactly 30. Current sum is: '.$sum],
            ]);
        }

        DB::transaction(function () use ($validated, $subjectAssignment) {
            InternalMarkComponent::where('subject_assignment_id', $subjectAssignment->id)->delete();

            foreach ($validated['components'] as $comp) {
                InternalMarkComponent::create([
                    'subject_assignment_id' => $subjectAssignment->id,
                    'name' => $comp['name'],
                    'max_marks' => $comp['max_marks'],
                ]);
            }
        });

        return redirect()
            ->route('marks.show', $subjectAssignment)
            ->with('status', 'Evaluation components configured successfully.');
    }

    public function show(SubjectAssignment $subjectAssignment): View
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isHod() || ($user->isFaculty() && (int) $subjectAssignment->faculty_id === (int) $user->facultyProfile?->id), 403);

        $components = InternalMarkComponent::where('subject_assignment_id', $subjectAssignment->id)->orderBy('id')->get();
        if ($components->isEmpty()) {
            if ($user->isFaculty()) {
                return view('marks.unconfigured_faculty', compact('subjectAssignment'));
            }
            return view('marks.unconfigured_guest', compact('subjectAssignment'));
        }

        $students = Student::with('user')
            ->where('class_section_id', $subjectAssignment->class_section_id)
            ->where('status', 'active')
            ->get()
            ->sortBy('roll_no');

        $marks = InternalMark::with('componentValues')
            ->where('subject_assignment_id', $subjectAssignment->id)
            ->get()
            ->keyBy('student_id');

        $hasSubmitted = $marks->contains(fn ($m) => $m->isSubmitted());
        $isEditable = ! $hasSubmitted && $user->isFaculty();

        // Calculate statistics
        $totals = $marks->pluck('total_50')->filter();
        $stats = [
            'highest' => $totals->max() ?? 0,
            'lowest' => $totals->min() ?? 0,
            'average' => $totals->count() > 0 ? round($totals->average(), 2) : 0,
            'pass_percentage' => $totals->count() > 0 ? round(($totals->filter(fn ($v) => $v >= 20.0)->count() / $totals->count()) * 100, 1) : 0,
        ];

        return view('marks.show', compact('subjectAssignment', 'components', 'students', 'marks', 'stats', 'isEditable', 'hasSubmitted'));
    }

    public function store(Request $request, SubjectAssignment $subjectAssignment): RedirectResponse
    {
        $this->authorizeAssignmentAccess($subjectAssignment);
        $this->ensureNotSubmitted($subjectAssignment);

        $components = InternalMarkComponent::where('subject_assignment_id', $subjectAssignment->id)->get();
        if ($components->isEmpty()) {
            return back()->withErrors(['marks' => 'Configure evaluation components first.']);
        }

        $validated = $request->validate([
            'mid_sem_30' => ['nullable', 'array'],
            'mid_sem_30.*' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'comp_marks' => ['nullable', 'array'],
            'comp_marks.*' => ['nullable', 'array'],
        ]);

        $students = Student::where('class_section_id', $subjectAssignment->class_section_id)
            ->where('status', 'active')
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($validated, $components, $subjectAssignment, $students) {
            foreach ($students as $studentId) {
                $rawMidSem = isset($validated['mid_sem_30'][$studentId]) && $validated['mid_sem_30'][$studentId] !== ''
                    ? (float) $validated['mid_sem_30'][$studentId]
                    : null;

                $scaledMidSem = $rawMidSem !== null ? round(($rawMidSem / 30.0) * 20.0, 2) : null;

                $internalMark = InternalMark::updateOrCreate(
                    [
                        'subject_assignment_id' => $subjectAssignment->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'mid_sem_30' => $rawMidSem,
                        'mid_sem_20' => $scaledMidSem,
                        'marked_by' => Auth::user()->id,
                    ]
                );

                $cieTotal = 0.00;
                $hasAnyCie = false;

                foreach ($components as $comp) {
                    $score = isset($validated['comp_marks'][$studentId][$comp->id]) && $validated['comp_marks'][$studentId][$comp->id] !== ''
                        ? (float) $validated['comp_marks'][$studentId][$comp->id]
                        : null;

                    if ($score !== null) {
                        $score = min($score, (float) $comp->max_marks);
                        $cieTotal += $score;
                        $hasAnyCie = true;
                    }

                    InternalMarkValue::updateOrCreate(
                        [
                            'internal_mark_id' => $internalMark->id,
                            'internal_marks_component_id' => $comp->id,
                        ],
                        [
                            'marks_obtained' => $score,
                        ]
                    );
                }

                $total50 = ($scaledMidSem ?? 0.00) + $cieTotal;

                $internalMark->update([
                    'cie_30' => $hasAnyCie ? round($cieTotal, 2) : 0.00,
                    'total_50' => round($total50, 2),
                ]);
            }
        });

        return redirect()
            ->route('marks.show', $subjectAssignment)
            ->with('status', 'Marks saved as draft successfully.');
    }

    public function submit(SubjectAssignment $subjectAssignment): RedirectResponse
    {
        $this->authorizeAssignmentAccess($subjectAssignment);
        $this->ensureNotSubmitted($subjectAssignment);

        InternalMark::where('subject_assignment_id', $subjectAssignment->id)
            ->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

        return redirect()
            ->route('marks.show', $subjectAssignment)
            ->with('status', 'Internal marks submitted and locked successfully.');
    }

    public function unlock(SubjectAssignment $subjectAssignment): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isHod(), 403);

        InternalMark::where('subject_assignment_id', $subjectAssignment->id)
            ->update([
                'status' => 'draft',
                'submitted_at' => null,
            ]);

        return redirect()
            ->route('marks.show', $subjectAssignment)
            ->with('status', 'Internal marks unlocked. Faculty can now edit them.');
    }

    public function studentView(): View
    {
        $user = Auth::user();
        abort_unless($user->isStudent(), 403);

        $student = Student::where('user_id', $user->id)->firstOrFail();

        $marks = InternalMark::with([
            'subjectAssignment.subject',
            'subjectAssignment.faculty.user',
            'componentValues.component'
        ])
        ->where('student_id', $student->id)
        ->where('status', 'submitted')
        ->get();

        return view('marks.student', compact('marks', 'student'));
    }

    public function export(SubjectAssignment $subjectAssignment): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isHod() || ($user->isFaculty() && (int) $subjectAssignment->faculty_id === (int) $user->facultyProfile?->id), 403);

        $components = InternalMarkComponent::where('subject_assignment_id', $subjectAssignment->id)->orderBy('id')->get();
        if ($components->isEmpty()) {
            abort(400, 'Evaluation components not configured yet.');
        }

        $students = Student::with('user')
            ->where('class_section_id', $subjectAssignment->class_section_id)
            ->where('status', 'active')
            ->get()
            ->sortBy('roll_no');

        $marks = InternalMark::with('componentValues')
            ->where('subject_assignment_id', $subjectAssignment->id)
            ->get()
            ->keyBy('student_id');

        $headers = [
            'Roll No',
            'Enrollment No',
            'Student Name',
            'Mid Sem Exam (30)',
            'Mid Sem Exam (20)',
        ];

        foreach ($components as $comp) {
            $headers[] = $comp->name . ' (' . (int)$comp->max_marks . ')';
        }

        $headers[] = 'CIE Total (30)';
        $headers[] = 'Total Marks (50)';

        $filename = sprintf(
            'internal-marks-%s-%s.csv',
            str_replace(' ', '-', strtolower($subjectAssignment->subject->subject_code)),
            str_replace(' ', '-', strtolower($subjectAssignment->classSection->section_name))
        );

        return response()->streamDownload(function () use ($headers, $students, $marks, $components) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headers);

            foreach ($students as $student) {
                $markRecord = $marks->get($student->id);
                $cieValues = $markRecord ? $markRecord->componentValues->keyBy('internal_marks_component_id') : collect();

                $row = [
                    $student->roll_no,
                    $student->enrollment_no,
                    $student->user->name,
                    $markRecord && $markRecord->mid_sem_30 !== null ? $markRecord->mid_sem_30 : '0.00',
                    $markRecord && $markRecord->mid_sem_20 !== null ? $markRecord->mid_sem_20 : '0.00',
                ];

                foreach ($components as $comp) {
                    $valRecord = $cieValues->get($comp->id);
                    $row[] = $valRecord && $valRecord->marks_obtained !== null ? $valRecord->marks_obtained : '0.00';
                }

                $row[] = $markRecord ? $markRecord->cie_30 : '0.00';
                $row[] = $markRecord ? $markRecord->total_50 : '0.00';

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function authorizeAssignmentAccess(SubjectAssignment $subjectAssignment): void
    {
        $user = Auth::user();
        abort_unless($user->isFaculty() && (int) $subjectAssignment->faculty_id === (int) $user->facultyProfile?->id, 403);
    }

    private function ensureNotSubmitted(SubjectAssignment $subjectAssignment): void
    {
        $hasSubmitted = InternalMark::where('subject_assignment_id', $subjectAssignment->id)
            ->where('status', 'submitted')
            ->exists();

        if ($hasSubmitted) {
            throw ValidationException::withMessages([
                'marks' => ['Marks have already been submitted and locked for this subject.'],
            ]);
        }
    }

    /**
     * @return list<int>
     */
    private function manageableDepartmentIds(): array
    {
        if (Auth::user()->isAdmin()) {
            return Department::query()->pluck('id')->all();
        }

        return Auth::user()
            ->facultyProfile()
            ->pluck('department_id')
            ->filter()
            ->values()
            ->all();
    }
}
