<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\InAppNotification;
use App\Models\LectureSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceCorrectionRequestController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isHod() || $user->isFaculty(), 403);

        $query = AttendanceCorrectionRequest::with([
            'lectureSession.subjectAssignment.subject',
            'lectureSession.subjectAssignment.classSection',
            'faculty.user',
            'requester',
            'decider',
        ])->latest();

        if ($user->isFaculty()) {
            $query->where('faculty_id', $user->facultyProfile?->id);
        }

        return view('attendance.corrections.index', [
            'requests' => $query->get(),
        ]);
    }

    public function store(Request $request, LectureSession $lectureSession): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isFaculty(), 403);

        $facultyId = $user->facultyProfile?->id;
        abort_unless($lectureSession->subjectAssignment()->where('faculty_id', $facultyId)->exists(), 403);

        if ($lectureSession->canEditAttendance()) {
            throw ValidationException::withMessages([
                'attendance' => 'This session is still editable. Please update attendance directly.',
            ]);
        }

        $lectureSession->load(['subjectAssignment.classSection.students', 'attendanceRecords']);
        $studentIds = $lectureSession->subjectAssignment->classSection->students->pluck('id')->all();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', Rule::in(['present', 'absent', 'absent_with_leave'])],
        ]);

        $records = $lectureSession->attendanceRecords->keyBy('student_id');
        $changes = [];

        foreach ($validated['attendance'] as $studentId => $newStatus) {
            $studentId = (int) $studentId;

            if (! in_array($studentId, $studentIds, true)) {
                continue;
            }

            $oldStatus = $records->get($studentId)?->status ?? 'absent';

            if ($oldStatus !== $newStatus) {
                $changes[$studentId] = [
                    'from' => $oldStatus,
                    'to' => $newStatus,
                ];
            }
        }

        if ($changes === []) {
            throw ValidationException::withMessages([
                'attendance' => 'Change at least one student status before requesting correction.',
            ]);
        }

        if ($lectureSession->correctionRequests()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'attendance' => 'A pending correction request already exists for this session.',
            ]);
        }

        $correctionRequest = AttendanceCorrectionRequest::create([
            'lecture_session_id' => $lectureSession->id,
            'faculty_id' => $facultyId,
            'requested_by' => $user->id,
            'reason' => $validated['reason'],
            'requested_changes' => $changes,
            'status' => 'pending',
        ]);

        $this->notifyDecisionMakers($correctionRequest);

        return redirect()
            ->route('attendance.show', $lectureSession)
            ->with('status', 'Attendance correction request sent to HOD.');
    }

    public function decide(Request $request, AttendanceCorrectionRequest $correctionRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isHod(), 403);

        if ($correctionRequest->status !== 'pending') {
            return back()->withErrors(['request' => 'This correction request has already been decided.']);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($correctionRequest, $validated, $user, $request) {
            $correctionRequest->load('lectureSession');

            if ($validated['status'] === 'approved') {
                foreach ($correctionRequest->requested_changes as $studentId => $change) {
                    AttendanceRecord::updateOrCreate(
                        [
                            'lecture_session_id' => $correctionRequest->lecture_session_id,
                            'student_id' => (int) $studentId,
                        ],
                        [
                            'status' => $change['to'],
                            'marked_by' => $user->id,
                            'marked_at' => now(),
                        ]
                    );
                }

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'attendance_correction_approved',
                    'entity_type' => AttendanceCorrectionRequest::class,
                    'entity_id' => $correctionRequest->id,
                    'new_values' => ['changes' => $correctionRequest->requested_changes],
                    'ip_address' => $request->ip(),
                ]);
            }

            $correctionRequest->update([
                'status' => $validated['status'],
                'decided_by' => $user->id,
                'decided_at' => now(),
                'decision_note' => $validated['decision_note'] ?? null,
            ]);
        });

        InAppNotification::create([
            'user_id' => $correctionRequest->requested_by,
            'title' => 'Attendance correction '.$validated['status'],
            'message' => 'Your correction request was '.$validated['status'].'.',
            'type' => $validated['status'] === 'approved' ? 'success' : 'warning',
        ]);

        return redirect()
            ->route('attendance-corrections.index')
            ->with('status', 'Correction request '.$validated['status'].'.');
    }

    private function notifyDecisionMakers(AttendanceCorrectionRequest $correctionRequest): void
    {
        User::query()
            ->whereIn('role', ['admin', 'hod'])
            ->where('status', 'active')
            ->get()
            ->each(function (User $user) use ($correctionRequest) {
                InAppNotification::create([
                    'user_id' => $user->id,
                    'title' => 'Attendance correction requested',
                    'message' => 'A faculty member requested correction for a locked attendance session.',
                    'type' => 'warning',
                ]);
            });
    }
}
