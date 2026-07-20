<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\LectureSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function show(LectureSession $lectureSession): View
    {
        $this->authorizeSessionAccess($lectureSession);

        $lectureSession->load([
            'subjectAssignment.faculty.user',
            'subjectAssignment.subject',
            'subjectAssignment.classSection.students.user',
            'attendanceRecords',
            'correctionRequests',
        ]);

        $user = Auth::user();

        $isPastSession = $lectureSession->lecture_date->lt(today());
        $pastAttendanceAllowed = false;
        
        if ($isPastSession) {
            $sessionDept = $lectureSession->subjectAssignment->classSection->program->department;
            if ($sessionDept?->allow_past_attendance) {
                if ($sessionDept->past_attendance_allow_date) {
                    $pastAttendanceAllowed = $lectureSession->lecture_date->gte($sessionDept->past_attendance_allow_date);
                } else {
                    $pastAttendanceAllowed = $lectureSession->lecture_date->gte(today()->subDays(10));
                }
            }
        }

        $canMarkAttendance = $lectureSession->canEditAttendance()
            && ($user->isFaculty() && (int) $lectureSession->subjectAssignment->faculty_id === (int) $user->facultyProfile?->id)
            && (! $isPastSession || $pastAttendanceAllowed);

        $canRequestCorrection = $user->isFaculty()
            && ! $canMarkAttendance
            && (int) $lectureSession->subjectAssignment->faculty_id === (int) $user->facultyProfile?->id
            && $lectureSession->attendanceRecords->isNotEmpty();
        $pendingCorrection = $lectureSession->correctionRequests
            ->where('status', 'pending')
            ->first();

        $activeLeaves = \App\Models\LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $lectureSession->lecture_date)
            ->whereDate('end_date', '>=', $lectureSession->lecture_date)
            ->get()
            ->keyBy('student_id');

        return view('attendance.show', [
            'session' => $lectureSession,
            'students' => $lectureSession->subjectAssignment->classSection->students->sortBy('roll_no'),
            'records' => $lectureSession->attendanceRecords->keyBy('student_id'),
            'canMarkAttendance' => $canMarkAttendance,
            'canRequestCorrection' => $canRequestCorrection,
            'pendingCorrection' => $pendingCorrection,
            'activeLeaves' => $activeLeaves,
        ]);
    }

    public function store(Request $request, LectureSession $lectureSession): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isFaculty() && (int) $lectureSession->subjectAssignment->faculty_id === (int) $user->facultyProfile?->id, 403);

        $this->authorizeSessionAccess($lectureSession);

        $validated = $request->validate([
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', Rule::in(['present', 'absent', 'absent_with_leave'])],
        ]);

        if (! $lectureSession->canEditAttendance()) {
            return back()->withErrors(['attendance' => 'This attendance session is locked and cannot be edited.']);
        }

        $isPastSession = $lectureSession->lecture_date->lt(today());
        if ($isPastSession) {
            $sessionDept = $lectureSession->subjectAssignment->classSection->program->department;
            $pastAttendanceAllowed = false;
            if ($sessionDept?->allow_past_attendance) {
                if ($sessionDept->past_attendance_allow_date) {
                    $pastAttendanceAllowed = $lectureSession->lecture_date->gte($sessionDept->past_attendance_allow_date);
                } else {
                    $pastAttendanceAllowed = $lectureSession->lecture_date->gte(today()->subDays(10));
                }
            }
            if (! $pastAttendanceAllowed) {
                return back()->withErrors(['attendance' => 'Past attendance marking is not allowed or has expired.']);
            }
        }

        DB::transaction(function () use ($validated, $lectureSession, $request) {
            foreach ($validated['attendance'] as $studentId => $status) {
                AttendanceRecord::updateOrCreate(
                    [
                        'lecture_session_id' => $lectureSession->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'status' => $status,
                        'marked_by' => $request->user()->id,
                        'marked_at' => now(),
                    ]
                );
            }

            $lectureSession->update([
                'status' => 'conducted',
                'submitted_at' => $lectureSession->submitted_at ?? now(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'attendance_submitted',
                'entity_type' => LectureSession::class,
                'entity_id' => $lectureSession->id,
                'new_values' => ['records' => count($validated['attendance'])],
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()
            ->route('attendance.show', $lectureSession)
            ->with('status', 'Attendance saved successfully.');
    }

    private function authorizeSessionAccess(LectureSession $lectureSession): void
    {
        $user = Auth::user();

        if ($user->isAdmin() || $user->isHod()) {
            return;
        }

        abort_unless($user->isFaculty(), 403);

        $facultyId = $user->facultyProfile?->id;
        abort_unless($lectureSession->subjectAssignment()->where('faculty_id', $facultyId)->exists(), 403);
    }
}
