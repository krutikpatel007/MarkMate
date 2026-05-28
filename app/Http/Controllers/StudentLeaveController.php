<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\InAppNotification;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentLeaveController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()->isStudent(), 403);

        $student = Auth::user()->student;
        $requests = LeaveRequest::with(['approver'])
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        return view('leaves.student', [
            'requests' => $requests,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isStudent(), 403);

        $student = $request->user()->student;

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('leaves', 'public');
        }

        DB::transaction(function () use ($student, $validated, $path, $request) {
            $leave = LeaveRequest::create([
                'student_id' => $student->id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'reason' => $validated['reason'],
                'attachment_path' => $path,
                'status' => 'pending',
            ]);

            // Notify HODs of student's department
            $studentDeptId = $student->program->department_id;
            $hods = User::where('role', 'hod')
                ->whereHas('facultyProfile', function ($query) use ($studentDeptId) {
                    $query->where('department_id', $studentDeptId);
                })
                ->get();
            foreach ($hods as $hod) {
                InAppNotification::create([
                    'user_id' => $hod->id,
                    'title' => 'New Leave Request',
                    'message' => "Student {$request->user()->name} has submitted a leave request from {$validated['start_date']} to {$validated['end_date']}.",
                    'type' => 'info',
                ]);
            }
        });

        return redirect()
            ->route('leaves.student.index')
            ->with('status', 'Leave request submitted successfully.');
    }

    public function hodIndex(): View
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isHod() || $user->isFaculty(), 403);

        if ($user->isAdmin()) {
            $requests = LeaveRequest::with(['student.user', 'approver', 'student.program', 'student.semester', 'student.classSection'])
                ->latest()
                ->get();
        } elseif ($user->isHod()) {
            $departmentId = $user->facultyProfile->department_id;
            $requests = LeaveRequest::with(['student.user', 'approver', 'student.program', 'student.semester', 'student.classSection'])
                ->whereHas('student.program', function ($query) use ($departmentId) {
                    $query->where('department_id', $departmentId);
                })
                ->latest()
                ->get();
        } else { // isFaculty
            $facultyId = $user->facultyProfile->id;
            $classSectionIds = \App\Models\SubjectAssignment::where('faculty_id', $facultyId)
                ->pluck('class_section_id')
                ->unique();

            $requests = LeaveRequest::with(['student.user', 'approver', 'student.program', 'student.semester', 'student.classSection'])
                ->whereHas('student', function ($query) use ($classSectionIds) {
                    $query->whereIn('class_section_id', $classSectionIds);
                })
                ->latest()
                ->get();
        }

        return view('leaves.hod', [
            'requests' => $requests,
        ]);
    }

    public function decide(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isHod(), 403);

        if ($user->isHod()) {
            $hodDeptId = $user->facultyProfile->department_id;
            $studentDeptId = $leaveRequest->student->program->department_id;
            abort_unless($hodDeptId === $studentDeptId, 403, 'You are not authorized to decide leave requests for other departments.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($leaveRequest->status === 'pending', 422, 'This request has already been decided.');

        DB::transaction(function () use ($request, $leaveRequest, $validated) {
            $leaveRequest->update([
                'status' => $validated['status'],
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'decision_note' => $validated['decision_note'] ?? null,
            ]);

            if ($validated['status'] === 'approved') {
                $records = AttendanceRecord::where('student_id', $leaveRequest->student_id)
                    ->whereHas('lectureSession', function ($query) use ($leaveRequest) {
                        $query->whereBetween('lecture_date', [$leaveRequest->start_date, $leaveRequest->end_date]);
                    })
                    ->get();

                foreach ($records as $record) {
                    $record->update([
                        'status' => 'absent_with_leave',
                        'marked_by' => $request->user()->id,
                        'marked_at' => now(),
                    ]);
                }
            }

            InAppNotification::create([
                'user_id' => $leaveRequest->student->user_id,
                'title' => "Leave Request " . ucfirst($validated['status']),
                'message' => "Your leave request from {$leaveRequest->start_date->toDateString()} to {$leaveRequest->end_date->toDateString()} has been {$validated['status']}.",
                'type' => $validated['status'] === 'approved' ? 'success' : 'warning',
            ]);
        });

        return redirect()
            ->route('leaves.hod.index')
            ->with('status', "Leave request {$validated['status']} successfully.");
    }
}
