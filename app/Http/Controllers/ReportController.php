<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesAcademicManagement;
use App\Models\AttendanceRecord;
use App\Models\ClassSection;
use App\Models\Faculty;
use App\Models\LectureSession;
use App\Models\Student;
use App\Models\SubjectAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use AuthorizesAcademicManagement;

    public function index(): View
    {
        $this->ensureAcademicManager();

        $studentSummaries = AttendanceRecord::query()
            ->select([
                'students.id',
                'students.enrollment_no',
                'users.name',
                DB::raw("sum(case when attendance_records.status = 'present' then 1 else 0 end) as present_count"),
                DB::raw("sum(case when attendance_records.status = 'absent' then 1 else 0 end) as absent_count"),
                DB::raw("sum(case when attendance_records.status = 'absent_with_leave' then 1 else 0 end) as leave_count"),
                DB::raw('count(*) as conducted_count'),
            ])
            ->join('students', 'students.id', '=', 'attendance_records.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->join('lecture_sessions', 'lecture_sessions.id', '=', 'attendance_records.lecture_session_id')
            ->join('programs', 'programs.id', '=', 'students.program_id')
            ->whereIn('programs.department_id', $this->manageableDepartmentIds())
            ->whereIn('lecture_sessions.status', ['conducted', 'locked'])
            ->groupBy('students.id', 'students.enrollment_no', 'users.name')
            ->orderBy('users.name')
            ->get()
            ->map(function ($row) {
                $row->percentage = $row->conducted_count > 0
                    ? round(($row->present_count / $row->conducted_count) * 100, 2)
                    : 0;

                return $row;
            });

        return view('reports.index', [
            'studentSummaries' => $studentSummaries,
            'defaulters' => $studentSummaries->where('percentage', '<', 75),
            'classSections' => ClassSection::with(['program', 'semester'])
                ->whereHas('program', fn ($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()))
                ->withCount('students')
                ->orderBy('display_name')
                ->get(),
            'subjectAssignments' => SubjectAssignment::with([
                'classSection.program.department',
                'classSection.semester',
                'subject',
                'faculty.user',
            ])
                ->whereHas('classSection.program', fn ($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()))
                ->where('status', 'active')
                ->get()
                ->sortBy(fn (SubjectAssignment $assignment) => $assignment->classSection->display_name.' '.$assignment->subject->subject_name),
            'dailySessions' => LectureSession::with([
                'subjectAssignment.faculty.user',
                'subjectAssignment.subject',
                'subjectAssignment.classSection',
                'attendanceRecords',
            ])
                ->whereHas('subjectAssignment.classSection.program', fn ($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()))
                ->whereDate('lecture_date', today())
                ->orderBy('start_time')
                ->get(),
            'faculty' => Faculty::with(['user', 'subjectAssignments.subject'])
                ->withCount('subjectAssignments')
                ->whereIn('department_id', $this->manageableDepartmentIds())
                ->get(),
            'totalStudents' => Student::whereHas('program', fn ($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()))->count(),
        ]);
    }

    public function exportClassAttendance(Request $request): StreamedResponse
    {
        $this->ensureAcademicManager();

        $validated = $request->validate([
            'class_section_id' => ['required', Rule::in($this->manageableClassSectionIds())],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $classSection = ClassSection::with(['program', 'semester'])->findOrFail($validated['class_section_id']);
        $this->authorizeClassSection($classSection);

        $rows = $this->classAttendanceRows(
            (int) $validated['class_section_id'],
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
        );

        $filename = $this->csvFileName($classSection, $validated['from_date'] ?? null, $validated['to_date'] ?? null);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'class',
                'enrollment_no',
                'roll_no',
                'student_name',
                'subject_code',
                'subject_name',
                'conducted_lectures',
                'present',
                'absent',
                'absent_with_leave',
                'attendance_percentage',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->class_name,
                    $row->enrollment_no,
                    $row->roll_no,
                    $row->student_name,
                    $row->subject_code,
                    $row->subject_name,
                    $row->conducted_count,
                    $row->present_count,
                    $row->absent_count,
                    $row->leave_count,
                    $row->percentage,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportSubjectAttendance(Request $request): StreamedResponse
    {
        $this->ensureAcademicManager();

        $validated = $request->validate([
            'subject_assignment_id' => ['required', Rule::in($this->manageableSubjectAssignmentIds())],
            'academic_term' => ['nullable', 'string', 'max:80'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $assignment = SubjectAssignment::with([
            'classSection.program.department',
            'classSection.semester',
            'subject',
            'faculty.user',
        ])->findOrFail($validated['subject_assignment_id']);
        $this->authorizeClassSection($assignment->classSection);

        $sessions = LectureSession::query()
            ->where('subject_assignment_id', $assignment->id)
            ->whereIn('status', ['conducted', 'locked'])
            ->when($validated['from_date'] ?? null, fn ($query, $date) => $query->whereDate('lecture_date', '>=', $date))
            ->when($validated['to_date'] ?? null, fn ($query, $date) => $query->whereDate('lecture_date', '<=', $date))
            ->orderBy('lecture_date')
            ->orderBy('start_time')
            ->orderBy('lecture_no')
            ->get();
        $students = Student::with('user')
            ->where('class_section_id', $assignment->class_section_id)
            ->where('status', 'active')
            ->orderBy('roll_no')
            ->orderBy('enrollment_no')
            ->get();
        $records = AttendanceRecord::query()
            ->whereIn('lecture_session_id', $sessions->pluck('id'))
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->student_id.'-'.$record->lecture_session_id);
        $filename = $this->subjectCsvFileName($assignment, $validated['from_date'] ?? null, $validated['to_date'] ?? null);

        return response()->streamDownload(function () use ($assignment, $sessions, $students, $records, $validated) {
            $handle = fopen('php://output', 'wb');

            foreach ($this->subjectAttendanceSheetRows(
                $assignment,
                $sessions,
                $students,
                $records,
                $validated['academic_term'] ?? null,
            ) as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return list<int>
     */
    private function manageableClassSectionIds(): array
    {
        return ClassSection::query()
            ->whereHas('program', fn ($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()))
            ->pluck('id')
            ->all();
    }

    /**
     * @return list<int>
     */
    private function manageableSubjectAssignmentIds(): array
    {
        return SubjectAssignment::query()
            ->whereHas('classSection.program', fn ($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()))
            ->pluck('id')
            ->all();
    }

    private function subjectAttendanceSheetRows(SubjectAssignment $assignment, $sessions, $students, $records, ?string $academicTerm): array
    {
        $rows = [
            ['SHREYARTH UNIVERSITY'],
            ['School Name: '.$assignment->classSection->program->department->department_name],
            ['Program Name: '.$assignment->classSection->program->program_name],
            ['Division (if given): '.$assignment->classSection->section_name],
            ['Attendance Sheet'],
            ['Year and Semester: '.$assignment->classSection->semester->semester_name],
            ['Course Code and Course Name: '.$assignment->subject->subject_code.' - '.$assignment->subject->subject_name],
            ['Course Incharge Name: '.$assignment->faculty->user->name],
            ['Academic Term: '.($academicTerm ?: $assignment->academic_year)],
            [],
        ];
        $dayRow = ['Sr. No', 'Enrollment No', 'Name of Student', 'DAY'];
        $dateRow = ['', '', '', 'DATE'];
        $numberRow = ['', '', '', 'NO.'];

        foreach ($sessions as $index => $session) {
            $dayRow[] = strtoupper($session->lecture_date->format('D'));
            $dateRow[] = $session->lecture_date->format('d/m/Y');
            $numberRow[] = (string) ($index + 1);
        }

        foreach (['No.of Absent', 'No.of Present', 'Percentage'] as $summaryColumn) {
            $dayRow[] = $summaryColumn;
            $dateRow[] = '';
            $numberRow[] = '';
        }

        $rows[] = $dayRow;
        $rows[] = $dateRow;
        $rows[] = $numberRow;

        foreach ($students as $index => $student) {
            $present = 0;
            $absent = 0;
            $row = [
                (string) ($index + 1),
                $student->enrollment_no,
                $student->user->name,
                '',
            ];

            foreach ($sessions as $session) {
                $record = $records->get($student->id.'-'.$session->id);
                $row[] = $this->statusMarker($record?->status);

                if ($record?->status === 'present') {
                    $present++;
                } elseif (in_array($record?->status, ['absent', 'absent_with_leave'], true)) {
                    $absent++;
                }
            }

            $total = $present + $absent;
            $row[] = (string) $absent;
            $row[] = (string) $present;
            $row[] = (string) ($total > 0 ? round(($present / $total) * 100, 2) : 0);
            $rows[] = $row;
        }

        return $rows;
    }

    private function statusMarker(?string $status): string
    {
        return match ($status) {
            'present' => 'P',
            'absent' => 'A',
            'absent_with_leave' => 'L',
            default => '',
        };
    }

    private function classAttendanceRows(int $classSectionId, ?string $fromDate, ?string $toDate)
    {
        return AttendanceRecord::query()
            ->select([
                'class_sections.display_name as class_name',
                'students.enrollment_no',
                'students.roll_no',
                'users.name as student_name',
                'subjects.subject_code',
                'subjects.subject_name',
                DB::raw("sum(case when attendance_records.status = 'present' then 1 else 0 end) as present_count"),
                DB::raw("sum(case when attendance_records.status = 'absent' then 1 else 0 end) as absent_count"),
                DB::raw("sum(case when attendance_records.status = 'absent_with_leave' then 1 else 0 end) as leave_count"),
                DB::raw('count(*) as conducted_count'),
            ])
            ->join('students', 'students.id', '=', 'attendance_records.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->join('lecture_sessions', 'lecture_sessions.id', '=', 'attendance_records.lecture_session_id')
            ->join('subject_assignments', 'subject_assignments.id', '=', 'lecture_sessions.subject_assignment_id')
            ->join('class_sections', 'class_sections.id', '=', 'subject_assignments.class_section_id')
            ->join('subjects', 'subjects.id', '=', 'subject_assignments.subject_id')
            ->where('subject_assignments.class_section_id', $classSectionId)
            ->whereIn('lecture_sessions.status', ['conducted', 'locked'])
            ->when($fromDate, fn ($query) => $query->whereDate('lecture_sessions.lecture_date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('lecture_sessions.lecture_date', '<=', $toDate))
            ->groupBy([
                'class_sections.display_name',
                'students.id',
                'students.enrollment_no',
                'students.roll_no',
                'users.name',
                'subjects.id',
                'subjects.subject_code',
                'subjects.subject_name',
            ])
            ->orderBy('students.roll_no')
            ->orderBy('students.enrollment_no')
            ->orderBy('subjects.subject_code')
            ->get()
            ->map(function ($row) {
                $row->percentage = $row->conducted_count > 0
                    ? round(($row->present_count / $row->conducted_count) * 100, 2)
                    : 0;

                return $row;
            });
    }

    private function csvFileName(ClassSection $classSection, ?string $fromDate, ?string $toDate): string
    {
        $name = strtolower($classSection->display_name);
        $name = preg_replace('/[^a-z0-9]+/', '-', $name) ?: 'class';
        $name = trim($name, '-');
        $range = $fromDate || $toDate
            ? '-'.($fromDate ?: 'start').'-to-'.($toDate ?: 'today')
            : '';

        return $name.'-attendance'.$range.'.csv';
    }

    private function subjectCsvFileName(SubjectAssignment $assignment, ?string $fromDate, ?string $toDate): string
    {
        $name = strtolower($assignment->classSection->display_name.'-'.$assignment->subject->subject_code);
        $name = preg_replace('/[^a-z0-9]+/', '-', $name) ?: 'subject';
        $name = trim($name, '-');
        $range = $fromDate || $toDate
            ? '-'.($fromDate ?: 'start').'-to-'.($toDate ?: 'today')
            : '';

        return $name.'-subject-attendance'.$range.'.csv';
    }
}
