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

    public function index(Request $request): View
    {
        $this->ensureAcademicManager();

        $semesterId = $request->query('semester_id');
        $classSectionId = $request->query('class_section_id');
        $subjectId = $request->query('subject_id');

        $query = AttendanceRecord::query()
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
            ->whereIn('lecture_sessions.status', ['conducted', 'locked']);

        if ($semesterId) {
            $query->where('students.semester_id', $semesterId);
        }

        if ($classSectionId) {
            $query->where('students.class_section_id', $classSectionId);
        }

        if ($subjectId) {
            $query->join('subject_assignments', 'subject_assignments.id', '=', 'lecture_sessions.subject_assignment_id')
                ->where('subject_assignments.subject_id', $subjectId);
        }

        $studentSummaries = $query
            ->groupBy('students.id', 'students.enrollment_no', 'users.name')
            ->orderBy('users.name')
            ->get()
            ->map(function ($row) {
                $row->percentage = $row->conducted_count > 0
                    ? round(($row->present_count / $row->conducted_count) * 100, 2)
                    : 0;

                return $row;
            });

        // Manageable Semesters, Classes, and Subjects for filter options
        $semesters = \App\Models\Semester::with('program')
            ->whereHas('program', fn($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()))
            ->get()
            ->sortBy(fn($s) => $s->program->program_code . ' Sem ' . $s->semester_no);

        $subjects = \App\Models\Subject::with('program')
            ->whereHas('program', fn($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()))
            ->get()
            ->sortBy(fn($s) => $s->program->program_code . ' - ' . $s->subject_name);

        return view('reports.index', [
            'studentSummaries' => $studentSummaries,
            'defaulters' => $studentSummaries->where('percentage', '<', 75),
            'semesters' => $semesters,
            'subjects' => $subjects,
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
            'session_type' => ['nullable', Rule::in(['regular', 'lab'])],
        ]);

        $classSection = ClassSection::with(['program', 'semester'])->findOrFail($validated['class_section_id']);
        $this->authorizeClassSection($classSection);

        $rows = $this->classAttendanceRows(
            (int) $validated['class_section_id'],
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
            $validated['session_type'] ?? null,
        );

        $filename = $this->csvFileName($classSection, $validated['from_date'] ?? null, $validated['to_date'] ?? null, $validated['session_type'] ?? null);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'class',
                'enrollment_no',
                'roll_no',
                'student_name',
                'subject_code',
                'subject_name',
                'session_type',
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
                    $row->session_type === 'lab' ? 'Lab' : 'Lecture',
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
            'session_type' => ['nullable', Rule::in(['regular', 'lab'])],
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
            ->when($validated['session_type'] ?? null, fn ($query, $type) => $query->where('session_type', $type))
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
            
        $filename = str_replace('.csv', '.xls', $this->subjectCsvFileName($assignment, $validated['from_date'] ?? null, $validated['to_date'] ?? null, $validated['session_type'] ?? null));

        return response()->streamDownload(function () use ($assignment, $sessions, $students, $records, $validated) {
            $academicTerm = $validated['academic_term'] ?? $assignment->academic_year;
            $colspan = count($sessions) + 1; // plus 1 empty cell column
            
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta charset="utf-8">';
            echo '<style>';
            echo 'body { font-family: Arial, sans-serif; }';
            echo 'table { border-collapse: collapse; }';
            echo 'td, th { border: 1px solid #000000; padding: 6px; font-size: 10pt; text-align: left; }';
            echo '.title { font-size: 16pt; font-weight: bold; color: #b91c1c; text-align: center; border: none; }';
            echo '.subtitle { font-size: 11pt; font-weight: bold; text-align: center; border: none; }';
            echo '.year-sem { font-size: 11pt; font-weight: bold; color: #1e3a8a; text-align: center; border: none; }';
            echo '.course-info { font-size: 11pt; font-weight: bold; color: #b91c1c; text-align: center; border: none; }';
            echo '.present { background-color: #dcfce7; color: #166534; text-align: center; font-weight: bold; }';
            echo '.absent { background-color: #fecaca; color: #991b1b; text-align: center; font-weight: bold; }';
            echo '.leave { background-color: #fef3c7; color: #92400e; text-align: center; font-weight: bold; }';
            echo '.hdr-main { background-color: #f1f5f9; font-weight: bold; text-align: center; color: #166534; }';
            echo '.summary-hdr { color: #166534; font-weight: bold; text-align: center; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';
            echo '<table>';
            
            // Logo (A1:B10) & Header Meta Rows (1-10)
            echo '<tr><td colspan="2" rowspan="10" style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; color: #64748b;">SHREYARTH UNIVERSITY LOGO</td>';
            echo '<td colspan="' . $colspan . '" class="title">SHREYARTH UNIVERSITY</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" class="subtitle">School Name: ' . $assignment->classSection->program->department->department_name . '</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" class="subtitle">Program Name: ' . $assignment->classSection->program->program_name . '</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" class="subtitle">Division (if given): ' . $assignment->classSection->section_name . '</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" class="subtitle">Attendance Sheet</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" class="year-sem">Year and Semester: ' . $assignment->classSection->semester->semester_name . '</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" class="course-info">Course Code and Course Name: ' . $assignment->subject->subject_code . ' - ' . $assignment->subject->subject_name . '</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" class="course-info">Course Incharge Name: ' . $assignment->faculty->user->name . '</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" class="course-info">Academic Term: ' . $academicTerm . '</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" style="border: none; height: 15px;"></td></tr>';
            
            // Grid Headers
            echo '<tr>';
            echo '<th rowspan="3" class="hdr-main">Sr. No</th>';
            echo '<th rowspan="3" class="hdr-main">Enrollment No</th>';
            echo '<th rowspan="3" class="hdr-main">Name of Student</th>';
            echo '<th class="hdr-main">DAY</th>';
            foreach ($sessions as $session) {
                echo '<td class="hdr-main">' . strtoupper($session->lecture_date->format('D')) . '</td>';
            }
            echo '<th rowspan="3" class="hdr-main summary-hdr">no of present</th>';
            echo '<th rowspan="3" class="hdr-main summary-hdr">no of absent</th>';
            echo '<th rowspan="3" class="hdr-main summary-hdr">percentage</th>';
            echo '</tr>';
            
            echo '<tr>';
            echo '<th class="hdr-main">DATE</th>';
            foreach ($sessions as $session) {
                echo '<td class="hdr-main">' . $session->lecture_date->format('d/m/Y') . '</td>';
            }
            echo '</tr>';
            
            echo '<tr>';
            echo '<th class="hdr-main">NO.</th>';
            foreach ($sessions as $index => $session) {
                echo '<td class="hdr-main">' . ($index + 1) . '</td>';
            }
            echo '</tr>';
            
            // Student Rows
            foreach ($students as $index => $student) {
                $present = 0;
                $absent = 0;
                
                echo '<tr>';
                echo '<td>' . ($index + 1) . '</td>';
                echo '<td>' . $student->enrollment_no . '</td>';
                echo '<td>' . $student->user->name . '</td>';
                echo '<td></td>'; // Divider column under Day/Date/No
                
                foreach ($sessions as $session) {
                    $record = $records->get($student->id . '-' . $session->id);
                    $status = $record?->status;
                    $class = '';
                    $marker = '';
                    
                    if ($status === 'present') {
                        $present++;
                        $class = 'present';
                        $marker = 'P';
                    } elseif ($status === 'absent') {
                        $absent++;
                        $class = 'absent';
                        $marker = 'A';
                    } elseif ($status === 'absent_with_leave') {
                        $absent++;
                        $class = 'leave';
                        $marker = 'L';
                    }
                    
                    echo '<td class="' . $class . '">' . $marker . '</td>';
                }
                
                $total = $present + $absent;
                $percentage = $total > 0 ? round(($present / $total) * 100) : 0;
                
                echo '<td style="text-align: right; font-weight: bold;">' . $present . '</td>';
                echo '<td style="text-align: right; font-weight: bold;">' . $absent . '</td>';
                echo '<td style="text-align: right; font-weight: bold;">' . $percentage . '%</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            echo '</body>';
            echo '</html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
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
        $typeRow = ['', '', '', 'TYPE'];
        $numberRow = ['', '', '', 'NO.'];

        foreach ($sessions as $index => $session) {
            $dayRow[] = strtoupper($session->lecture_date->format('D'));
            $dateRow[] = $session->lecture_date->format('d/m/Y');
            $typeRow[] = $session->session_type === 'lab' ? 'LAB' : 'LECTURE';
            $numberRow[] = (string) ($index + 1);
        }

        foreach (['No.of Absent', 'No.of Present', 'Percentage'] as $summaryColumn) {
            $dayRow[] = $summaryColumn;
            $dateRow[] = '';
            $typeRow[] = '';
            $numberRow[] = '';
        }

        $rows[] = $dayRow;
        $rows[] = $dateRow;
        $rows[] = $typeRow;
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

    private function classAttendanceRows(int $classSectionId, ?string $fromDate, ?string $toDate, ?string $sessionType = null)
    {
        return AttendanceRecord::query()
            ->select([
                'class_sections.display_name as class_name',
                'students.enrollment_no',
                'students.roll_no',
                'users.name as student_name',
                'subjects.subject_code',
                'subjects.subject_name',
                'lecture_sessions.session_type',
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
            ->when($sessionType, fn ($query) => $query->where('lecture_sessions.session_type', $sessionType))
            ->groupBy([
                'class_sections.display_name',
                'students.id',
                'students.enrollment_no',
                'students.roll_no',
                'users.name',
                'subjects.id',
                'subjects.subject_code',
                'subjects.subject_name',
                'lecture_sessions.session_type',
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

    private function csvFileName(ClassSection $classSection, ?string $fromDate, ?string $toDate, ?string $sessionType = null): string
    {
        $name = strtolower($classSection->display_name);
        $name = preg_replace('/[^a-z0-9]+/', '-', $name) ?: 'class';
        $name = trim($name, '-');
        $typeSuffix = $sessionType ? '-'.$sessionType : '';
        $range = $fromDate || $toDate
            ? '-'.($fromDate ?: 'start').'-to-'.($toDate ?: 'today')
            : '';

        return $name.$typeSuffix.'-attendance'.$range.'.csv';
    }

    private function subjectCsvFileName(SubjectAssignment $assignment, ?string $fromDate, ?string $toDate, ?string $sessionType = null): string
    {
        $name = strtolower($assignment->classSection->display_name.'-'.$assignment->subject->subject_code);
        $name = preg_replace('/[^a-z0-9]+/', '-', $name) ?: 'subject';
        $name = trim($name, '-');
        $typeSuffix = $sessionType ? '-'.$sessionType : '';
        $range = $fromDate || $toDate
            ? '-'.($fromDate ?: 'start').'-to-'.($toDate ?: 'today')
            : '';

        return $name.$typeSuffix.'-subject-attendance'.$range.'.csv';
    }
}
