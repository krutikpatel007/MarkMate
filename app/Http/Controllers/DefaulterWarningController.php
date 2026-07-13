<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassSection;
use App\Models\AuditLog;
use App\Models\InAppNotification;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DefaulterWarningController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isHod(), 403);

        $user = Auth::user();
        $isHod = $user->isHod();
        $manageableDeptIds = $isHod ? [$user->facultyProfile->department_id] : Department::pluck('id')->toArray();

        // Query students whose cumulative attendance is below 75%
        $defaulters = AttendanceRecord::query()
            ->select([
                'students.id',
                'students.enrollment_no',
                'students.roll_no',
                'students.class_section_id',
                'students.program_id',
                'students.semester_id',
                'users.name as student_name',
                DB::raw("sum(case when attendance_records.status = 'present' then 1 else 0 end) as present_count"),
                DB::raw('count(*) as conducted_count'),
            ])
            ->join('students', 'students.id', '=', 'attendance_records.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->join('lecture_sessions', 'lecture_sessions.id', '=', 'attendance_records.lecture_session_id')
            ->whereIn('lecture_sessions.status', ['conducted', 'locked'])
            ->whereIn('students.program_id', Program::whereIn('department_id', $manageableDeptIds)->pluck('id'))
            ->groupBy('students.id', 'students.enrollment_no', 'students.roll_no', 'students.class_section_id', 'students.program_id', 'students.semester_id', 'users.name')
            ->get()
            ->map(function ($row) {
                $row->percentage = $row->conducted_count > 0
                    ? round(($row->present_count / $row->conducted_count) * 100, 2)
                    : 0;
                return $row;
            })
            ->filter(fn($row) => $row->percentage < 75.0)
            ->values();

        // Eager load relationships for filtered defaulters
        $studentIds = $defaulters->pluck('id')->toArray();
        $students = Student::with(['user', 'program', 'semester', 'classSection'])
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        foreach ($defaulters as $def) {
            $s = $students->get($def->id);
            if ($s) {
                $def->student = $s;
            }
        }

        // Fetch recent parent notifications from audit logs
        $alertLogs = AuditLog::where('action', 'send_parent_alert')
            ->whereIn('entity_id', $studentIds)
            ->where('entity_type', 'App\Models\Student')
            ->latest()
            ->get()
            ->groupBy('entity_id');

        return view('defaulters.index', [
            'defaulters' => $defaulters,
            'alertLogs' => $alertLogs,
        ]);
    }

    public function show(Student $student): View
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isHod(), 403);

        if ($user->isHod()) {
            $hodDeptId = $user->facultyProfile->department_id;
            $studentDeptId = $student->program->department_id;
            abort_unless($hodDeptId === $studentDeptId, 403, 'You are not authorized to view warning letters for other departments.');
        }

        $student->load(['user', 'program.department', 'semester', 'classSection']);

        // Fetch subject-wise breakdown for the letter details
        $subjectStats = AttendanceRecord::query()
            ->select([
                'subjects.subject_code',
                'subjects.subject_name',
                DB::raw("sum(case when attendance_records.status = 'present' then 1 else 0 end) as present_count"),
                DB::raw('count(*) as conducted_count'),
            ])
            ->join('lecture_sessions', 'lecture_sessions.id', '=', 'attendance_records.lecture_session_id')
            ->join('subject_assignments', 'subject_assignments.id', '=', 'lecture_sessions.subject_assignment_id')
            ->join('subjects', 'subjects.id', '=', 'subject_assignments.subject_id')
            ->where('attendance_records.student_id', $student->id)
            ->whereIn('lecture_sessions.status', ['conducted', 'locked'])
            ->groupBy('subjects.id', 'subjects.subject_code', 'subjects.subject_name')
            ->get()
            ->map(function ($row) {
                $row->percentage = $row->conducted_count > 0
                    ? round(($row->present_count / $row->conducted_count) * 100, 2)
                    : 0;
                return $row;
            });

        // Compute cumulative overall percentage
        $overallConducted = $subjectStats->sum('conducted_count');
        $overallPresent = $subjectStats->sum('present_count');
        $overallPercentage = $overallConducted > 0 ? round(($overallPresent / $overallConducted) * 100, 2) : 0;

        return view('defaulters.warning_letter', [
            'student' => $student,
            'subjectStats' => $subjectStats,
            'overallConducted' => $overallConducted,
            'overallPresent' => $overallPresent,
            'overallPercentage' => $overallPercentage,
        ]);
    }

    public function sendParentAlert(Request $request, Student $student): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isHod(), 403);

        if ($user->isHod()) {
            $hodDeptId = $user->facultyProfile->department_id;
            $studentDeptId = $student->program->department_id;
            abort_unless($hodDeptId === $studentDeptId, 403, 'You are not authorized to issue alerts for students of other departments.');
        }

        $student->load('user');

        DB::transaction(function () use ($user, $student) {
            // 1. Create audit log
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'send_parent_alert',
                'entity_type' => 'App\Models\Student',
                'entity_id' => $student->id,
                'new_values' => [
                    'student_name' => $student->user->name,
                    'enrollment_no' => $student->enrollment_no,
                    'parent_notified_at' => now()->toDateTimeString(),
                    'notified_by' => $user->name,
                ]
            ]);

            // 2. Create in-app warning notification for student
            InAppNotification::create([
                'user_id' => $student->user_id,
                'title' => 'Parent Defaulter Alert Issued',
                'message' => "An official warning notification regarding your low attendance ({$student->enrollment_no}) has been dispatched to your parents/guardians.",
                'type' => 'warning',
            ]);
        });

        return redirect()
            ->back()
            ->with('status', "Official parent warning alert has been simulated and logged successfully for {$student->user->name}.");
    }

    public function sendClassAlerts(Request $request, ClassSection $classSection): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isHod(), 403);

        if ($user->isHod()) {
            $hodDeptId = $user->facultyProfile->department_id;
            $classDeptId = $classSection->program->department_id;
            abort_unless($hodDeptId === $classDeptId, 403, 'You are not authorized to issue alerts for this department.');
        }

        // Query students in this class section whose cumulative attendance is below 75%
        $defaulters = AttendanceRecord::query()
            ->select([
                'students.id',
                'students.user_id',
                'students.enrollment_no',
                'users.name as student_name',
                DB::raw("sum(case when attendance_records.status = 'present' then 1 else 0 end) as present_count"),
                DB::raw('count(*) as conducted_count'),
            ])
            ->join('students', 'students.id', '=', 'attendance_records.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->join('lecture_sessions', 'lecture_sessions.id', '=', 'attendance_records.lecture_session_id')
            ->where('students.class_section_id', $classSection->id)
            ->whereIn('lecture_sessions.status', ['conducted', 'locked'])
            ->groupBy('students.id', 'students.user_id', 'students.enrollment_no', 'users.name')
            ->get()
            ->map(function ($row) {
                $row->percentage = $row->conducted_count > 0
                    ? round(($row->present_count / $row->conducted_count) * 100, 2)
                    : 0;
                return $row;
            })
            ->filter(fn($row) => $row->percentage < 75.0)
            ->values();

        if ($defaulters->isEmpty()) {
            return back()->with('status', "No defaulter students found in {$classSection->display_name}.");
        }

        DB::transaction(function () use ($user, $defaulters) {
            foreach ($defaulters as $def) {
                // 1. Create audit log
                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'send_parent_alert',
                    'entity_type' => 'App\Models\Student',
                    'entity_id' => $def->id,
                    'new_values' => [
                        'student_name' => $def->student_name,
                        'enrollment_no' => $def->enrollment_no,
                        'parent_notified_at' => now()->toDateTimeString(),
                        'notified_by' => $user->name,
                    ]
                ]);

                // 2. Create in-app warning notification for student
                InAppNotification::create([
                    'user_id' => $def->user_id,
                    'title' => 'Parent Defaulter Alert Issued',
                    'message' => "An official warning notification regarding your low attendance ({$def->enrollment_no}) has been dispatched to your parents/guardians.",
                    'type' => 'warning',
                ]);
            }
        });

        return redirect()
            ->back()
            ->with('status', "Official parent warning alerts dispatched for all " . $defaulters->count() . " defaulters in {$classSection->display_name}.");
    }
}
