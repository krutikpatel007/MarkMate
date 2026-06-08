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

        $status = $marks->isNotEmpty() ? $marks->first()->status : 'draft';
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';

        if ($isExamDept && ! $user->isAdmin()) {
            // Exam Department user can ONLY see marks if they have been submitted to EXAM!
            if ($status !== 'submitted_to_exam' && $status !== 'submitted') {
                return view('marks.unsubmitted_to_exam', compact('subjectAssignment'));
            }
        }

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

        return view('marks.show', compact('subjectAssignment', 'components', 'students', 'marks', 'stats', 'isEditable', 'hasSubmitted', 'status'));
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
        
        $hasSubmitted = InternalMark::where('subject_assignment_id', $subjectAssignment->id)
            ->whereIn('status', ['submitted_to_hod', 'submitted_to_exam', 'submitted'])
            ->exists();

        if ($hasSubmitted) {
            throw ValidationException::withMessages([
                'marks' => ['Marks have already been submitted.'],
            ]);
        }

        InternalMark::where('subject_assignment_id', $subjectAssignment->id)
            ->update([
                'status' => 'submitted_to_hod',
                'submitted_at' => now(),
            ]);

        return redirect()
            ->route('marks.show', $subjectAssignment)
            ->with('status', 'Internal marks submitted to HOD successfully.');
    }

    public function submitToExam(SubjectAssignment $subjectAssignment): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isHod() && in_array((int)$subjectAssignment->classSection->program->department_id, $this->manageableDepartmentIds(), true), 403);

        $marks = InternalMark::where('subject_assignment_id', $subjectAssignment->id)->get();
        if ($marks->isEmpty() || $marks->contains(fn ($m) => $m->status !== 'submitted_to_hod')) {
            return back()->withErrors(['marks' => 'Marks must be submitted to HOD and be in HOD review state before sending to Exam Department.']);
        }

        InternalMark::where('subject_assignment_id', $subjectAssignment->id)
            ->update([
                'status' => 'submitted_to_exam',
                'submitted_at' => now(),
            ]);

        return redirect()
            ->route('marks.show', $subjectAssignment)
            ->with('status', 'Internal marks submitted to Exam Department successfully.');
    }

    public function unlock(SubjectAssignment $subjectAssignment): RedirectResponse
    {
        $user = Auth::user();
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';
        
        if ($user->isAdmin()) {
            $allowed = true;
        } elseif ($user->isHod() || $user->isCoe()) {
            if ($isExamDept) {
                // Exam HOD can unlock marks that are submitted to EXAM
                $hasSubmittedToExam = InternalMark::where('subject_assignment_id', $subjectAssignment->id)
                    ->whereIn('status', ['submitted_to_exam', 'submitted'])
                    ->exists();
                $allowed = $hasSubmittedToExam;
            } else {
                // Academic HOD can unlock marks that are submitted to HOD
                $hasSubmittedToHod = InternalMark::where('subject_assignment_id', $subjectAssignment->id)
                    ->where('status', 'submitted_to_hod')
                    ->exists();
                $allowed = $hasSubmittedToHod && in_array((int)$subjectAssignment->classSection->program->department_id, $this->manageableDepartmentIds(), true);
            }
        } else {
            $allowed = false;
        }

        abort_unless($allowed, 403);

        InternalMark::where('subject_assignment_id', $subjectAssignment->id)
            ->update([
                'status' => 'draft',
                'submitted_at' => null,
            ]);

        return redirect()
            ->route('marks.show', $subjectAssignment)
            ->with('status', 'Internal marks unlocked. Faculty can now edit them.');
    }

    public function releaseExternal(SubjectAssignment $subjectAssignment): RedirectResponse
    {
        $user = Auth::user();
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';
        abort_unless($user->isAdmin() || $user->isCoe() || ($user->isHod() && $isExamDept), 403);

        // Verify that internal marks are submitted to exam department or fully submitted/locked
        $hasSubmittedToExam = InternalMark::where('subject_assignment_id', $subjectAssignment->id)
            ->whereIn('status', ['submitted_to_exam', 'submitted'])
            ->exists();

        if (!$hasSubmittedToExam) {
            return back()->withErrors(['marks' => 'Internal marks must be submitted to the Exam Department first before releasing external marks entry.']);
        }

        $subjectAssignment->update([
            'external_marks_status' => 'released',
        ]);

        return redirect()
            ->route('marks.show', $subjectAssignment)
            ->with('status', 'External marks entry has been released for this subject.');
    }

    public function storeExternal(Request $request, SubjectAssignment $subjectAssignment): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || ($user->isFaculty() && (int) $subjectAssignment->faculty_id === (int) $user->facultyProfile?->id), 403);
        abort_unless($subjectAssignment->external_marks_status === 'released', 403);

        $validated = $request->validate([
            'external_marks' => ['required', 'array'],
            'external_marks.*' => ['nullable', 'numeric', 'min:0', 'max:50'],
        ]);

        DB::transaction(function () use ($validated, $subjectAssignment) {
            foreach ($validated['external_marks'] as $studentId => $mark) {
                $rawMark = $mark !== null && $mark !== '' ? (float) $mark : null;

                $internalMark = InternalMark::where('subject_assignment_id', $subjectAssignment->id)
                    ->where('student_id', $studentId)
                    ->first();

                if ($internalMark) {
                    $total100 = ($internalMark->total_50 ?? 0.00) + ($rawMark ?? 0.00);
                    $internalMark->update([
                        'external_50' => $rawMark,
                        'total_100' => round($total100, 2),
                    ]);
                }
            }
        });

        return redirect()
            ->route('marks.show', $subjectAssignment)
            ->with('status', 'External marks saved as draft successfully.');
    }

    public function submitExternal(Request $request, SubjectAssignment $subjectAssignment): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || ($user->isFaculty() && (int) $subjectAssignment->faculty_id === (int) $user->facultyProfile?->id), 403);
        abort_unless($subjectAssignment->external_marks_status === 'released', 403);

        $validated = $request->validate([
            'external_marks' => ['required', 'array'],
            'external_marks.*' => ['nullable', 'numeric', 'min:0', 'max:50'],
        ]);

        DB::transaction(function () use ($validated, $subjectAssignment) {
            foreach ($validated['external_marks'] as $studentId => $mark) {
                $rawMark = $mark !== null && $mark !== '' ? (float) $mark : null;

                $internalMark = InternalMark::where('subject_assignment_id', $subjectAssignment->id)
                    ->where('student_id', $studentId)
                    ->first();

                if ($internalMark) {
                    $total100 = ($internalMark->total_50 ?? 0.00) + ($rawMark ?? 0.00);
                    $internalMark->update([
                        'external_50' => $rawMark,
                        'total_100' => round($total100, 2),
                    ]);
                }
            }

            $subjectAssignment->update([
                'external_marks_status' => 'submitted',
            ]);
        });

        return redirect()
            ->route('marks.show', $subjectAssignment)
            ->with('status', 'External marks submitted and locked successfully.');
    }

    public function importExternalTemplate(SubjectAssignment $subjectAssignment)
    {
        $this->authorizeAssignmentAccess($subjectAssignment);
        abort_unless($subjectAssignment->external_marks_status === 'released', 403);

        $students = Student::with('user')
            ->where('class_section_id', $subjectAssignment->class_section_id)
            ->where('status', 'active')
            ->get()
            ->sortBy('roll_no');

        $headers = ['Roll No', 'Enrollment No', 'Student Name', 'External Mark (50)'];
        $filename = sprintf('external-marks-template-%s.csv', str_replace(' ', '-', strtolower($subjectAssignment->subject->subject_code)));

        return response()->streamDownload(function () use ($headers, $students) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headers);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->roll_no,
                    $student->enrollment_no,
                    $student->user->name,
                    ''
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function importExternal(Request $request, SubjectAssignment $subjectAssignment): RedirectResponse
    {
        $this->authorizeAssignmentAccess($subjectAssignment);
        abort_unless($subjectAssignment->external_marks_status === 'released', 403);

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle); // skip header row
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) >= 4) {
                    $rows[] = [
                        'roll_no' => $data[0],
                        'enrollment_no' => $data[1],
                        'external_mark' => $data[3],
                    ];
                }
            }
            fclose($handle);
        }

        DB::transaction(function () use ($rows, $subjectAssignment) {
            foreach ($rows as $row) {
                $student = Student::where('class_section_id', $subjectAssignment->class_section_id)
                    ->where('enrollment_no', $row['enrollment_no'])
                    ->first();

                if ($student) {
                    $rawMark = $row['external_mark'] !== '' && $row['external_mark'] !== null ? (float)$row['external_mark'] : null;
                    if ($rawMark !== null) {
                        $rawMark = max(0.00, min(50.00, $rawMark));
                    }

                    $internalMark = InternalMark::where('subject_assignment_id', $subjectAssignment->id)
                        ->where('student_id', $student->id)
                        ->first();

                    if ($internalMark) {
                        $total100 = ($internalMark->total_50 ?? 0.00) + ($rawMark ?? 0.00);
                        $internalMark->update([
                            'external_50' => $rawMark,
                            'total_100' => round($total100, 2),
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route('marks.show', $subjectAssignment)
            ->with('status', 'External marks imported from CSV successfully.');
    }

    public function studentSemesterReport(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user->isStudent(), 403);

        $student = Student::with(['program', 'semester', 'classSection'])->where('user_id', $user->id)->firstOrFail();

        if (!$student->classSection->results_released) {
            return view('marks.results_locked', compact('student'));
        }

        return $this->generateSemesterReportView($student);
    }

    public function semesterReport(Request $request, Student $student): View
    {
        $user = Auth::user();
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';
        abort_unless($user->isAdmin() || $user->isHod() || $user->isCoe() || $user->isAdminStaff() || ($user->isFaculty() && $isExamDept), 403);

        return $this->generateSemesterReportView($student);
    }

    private function generateSemesterReportView(Student $student): View
    {
        // Get all active subjects for this program and semester
        $subjects = \App\Models\Subject::where('program_id', $student->program_id)
            ->where('semester_id', $student->semester_id)
            ->where('status', 'active')
            ->get();

        $subjectIds = $subjects->pluck('id')->all();
        $assignments = SubjectAssignment::with('subject')
            ->whereIn('subject_id', $subjectIds)
            ->where('class_section_id', $student->class_section_id)
            ->where('status', 'active')
            ->get()
            ->keyBy('subject_id');

        $assignmentIds = $assignments->pluck('id')->all();
        $marks = InternalMark::with('subjectAssignment.subject')
            ->where('student_id', $student->id)
            ->whereIn('subject_assignment_id', $assignmentIds)
            ->get()
            ->keyBy('subjectAssignment.subject_id');

        $fullyDeclared = true;
        $hasAnySubmitted = false;
        foreach ($subjects as $sub) {
            $assign = $assignments->get($sub->id);
            if (!$assign || $assign->external_marks_status !== 'submitted') {
                $fullyDeclared = false;
            } else {
                $hasAnySubmitted = true;
            }
        }

        $reportData = [];
        $totalCredits = 0;
        $earnedCredits = 0;
        $weightedPoints = 0.00;

        foreach ($subjects as $sub) {
            $mark = $marks->get($sub->id);
            $assign = $assignments->get($sub->id);

            $cie = $mark ? $mark->total_50 : 0.00;
            $external = $mark ? $mark->external_50 : null;
            $total = $mark && $external !== null ? $mark->total_100 : null;

            $gradeDetails = $total !== null ? $this->calculateGradeDetails($total) : ['grade' => '-', 'point' => 0];

            $reportData[] = [
                'subject_code' => $sub->subject_code,
                'subject_name' => $sub->subject_name,
                'credits' => $sub->credits,
                'cie' => $cie,
                'external' => $external,
                'total' => $total,
                'grade' => $gradeDetails['grade'],
                'grade_point' => $gradeDetails['point'],
                'external_submitted' => $assign && $assign->external_marks_status === 'submitted',
            ];

            if ($assign && $assign->external_marks_status === 'submitted') {
                $totalCredits += $sub->credits;
                if ($gradeDetails['grade'] !== 'F') {
                    $earnedCredits += $sub->credits;
                }
                $weightedPoints += ($sub->credits * $gradeDetails['point']);
            }
        }

        $sgpa = $totalCredits > 0 ? round($weightedPoints / $totalCredits, 2) : 0.00;

        $hashPayload = sprintf('%s-%s-%s-%s-%s', $student->enrollment_no, $student->user->name, $student->semester->semester_no, $sgpa, config('app.key'));
        $validationHash = hash('sha256', $hashPayload);

        return view('marks.semester_report', compact('student', 'reportData', 'totalCredits', 'earnedCredits', 'sgpa', 'fullyDeclared', 'validationHash'));
    }

    private function calculateGradeDetails(float $totalMarks): array
    {
        if ($totalMarks >= 90) {
            return ['grade' => 'O', 'point' => 10];
        } elseif ($totalMarks >= 85) {
            return ['grade' => 'A+', 'point' => 9];
        } elseif ($totalMarks >= 80) {
            return ['grade' => 'A', 'point' => 8];
        } elseif ($totalMarks >= 70) {
            return ['grade' => 'B+', 'point' => 7];
        } elseif ($totalMarks >= 60) {
            return ['grade' => 'B', 'point' => 6];
        } elseif ($totalMarks >= 50) {
            return ['grade' => 'C', 'point' => 5];
        } elseif ($totalMarks >= 40) {
            return ['grade' => 'P', 'point' => 4];
        } else {
            return ['grade' => 'F', 'point' => 0];
        }
    }

    public function studentView(): View
    {
        $user = Auth::user();
        abort_unless($user->isStudent(), 403);

        $student = Student::with(['program', 'semester', 'classSection'])->where('user_id', $user->id)->firstOrFail();

        if (!$student->classSection->results_released) {
            return view('marks.results_locked', compact('student'));
        }

        return $this->generateSemesterReportView($student);
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

        $isExternalEnabled = in_array($subjectAssignment->external_marks_status, ['released', 'submitted'], true);

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

        if ($isExternalEnabled) {
            $headers[] = 'External Marks (50)';
            $headers[] = 'Total Marks (100)';
        }

        $filename = sprintf(
            'internal-marks-%s-%s.csv',
            str_replace(' ', '-', strtolower($subjectAssignment->subject->subject_code)),
            str_replace(' ', '-', strtolower($subjectAssignment->classSection->section_name))
        );

        return response()->streamDownload(function () use ($headers, $students, $marks, $components, $isExternalEnabled) {
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

                if ($isExternalEnabled) {
                    $row[] = $markRecord && $markRecord->external_50 !== null ? $markRecord->external_50 : '0.00';
                    $row[] = $markRecord && $markRecord->total_100 !== null ? $markRecord->total_100 : '0.00';
                }

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
            ->whereIn('status', ['submitted_to_hod', 'submitted_to_exam', 'submitted'])
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
    public function toggleResultsRelease(\App\Models\ClassSection $classSection): RedirectResponse
    {
        $user = Auth::user();
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';
        abort_unless($user->isAdmin() || $user->isCoe() || ($user->isHod() && $isExamDept), 403);

        $classSection->update([
            'results_released' => !$classSection->results_released,
        ]);

        $statusMessage = $classSection->results_released
            ? 'Results released successfully for ' . $classSection->display_name
            : 'Results locked successfully for ' . $classSection->display_name;

        return back()->with('status', $statusMessage);
    }

    private function manageableDepartmentIds(): array
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return Department::query()->pluck('id')->all();
        }

        // Central Exam Department has global visibility across all academic departments
        $userDeptCode = $user->facultyProfile?->department?->department_code;
        if ($user->isCoe() || $user->isAdminStaff() || $userDeptCode === 'EXAM') {
            return Department::query()->pluck('id')->all();
        }

        return $user
            ->facultyProfile()
            ->pluck('department_id')
            ->filter()
            ->values()
            ->all();
    }
}
