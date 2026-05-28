<?php

namespace App\Http\Controllers;

use App\Models\FacultyLeaveRequest;
use App\Models\InAppNotification;
use App\Models\LectureSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FacultyLeaveController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()->isFaculty(), 403);

        $faculty = Auth::user()->facultyProfile;
        $requests = FacultyLeaveRequest::with(['approver'])
            ->where('faculty_id', $faculty->id)
            ->latest()
            ->get();

        return view('leaves.faculty', [
            'requests' => $requests,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isFaculty(), 403);

        $faculty = $request->user()->facultyProfile;

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('faculty-leaves', 'public');
        }

        DB::transaction(function () use ($faculty, $validated, $path, $request) {
            $leave = FacultyLeaveRequest::create([
                'faculty_id' => $faculty->id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'reason' => $validated['reason'],
                'attachment_path' => $path,
                'status' => 'pending',
            ]);

            $facultyDeptId = $faculty->department_id;
            $hodsAndAdmins = User::where(function ($query) use ($facultyDeptId) {
                $query->where('role', 'hod')
                    ->whereHas('facultyProfile', function ($q) use ($facultyDeptId) {
                        $q->where('department_id', $facultyDeptId);
                    });
            })->orWhere('role', 'admin')->get();
            foreach ($hodsAndAdmins as $user) {
                InAppNotification::create([
                    'user_id' => $user->id,
                    'title' => 'New Faculty Leave Request',
                    'message' => "Faculty {$request->user()->name} has submitted a leave request from {$validated['start_date']} to {$validated['end_date']}.",
                    'type' => 'info',
                ]);
            }
        });

        return redirect()
            ->route('leaves.faculty.index')
            ->with('status', 'Leave request submitted successfully.');
    }

    public function hodIndex(): View
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isHod(), 403);

        if ($user->isHod()) {
            $requests = FacultyLeaveRequest::with(['faculty.user', 'approver', 'faculty.department'])
                ->whereHas('faculty', function ($q) use ($user) {
                    $q->where('department_id', $user->facultyProfile->department_id);
                })
                ->latest()
                ->get();
        } else {
            $requests = FacultyLeaveRequest::with(['faculty.user', 'approver', 'faculty.department'])
                ->latest()
                ->get();
        }

        return view('leaves.hod_faculty', [
            'requests' => $requests,
        ]);
    }

    public function decide(Request $request, FacultyLeaveRequest $facultyLeaveRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isHod(), 403);

        if ($user->isHod()) {
            $hodDeptId = $user->facultyProfile->department_id;
            $facultyDeptId = $facultyLeaveRequest->faculty->department_id;
            abort_unless($hodDeptId === $facultyDeptId, 403, 'You are not authorized to decide leave requests for other departments.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($facultyLeaveRequest->status === 'pending', 422, 'This request has already been decided.');

        DB::transaction(function () use ($request, $facultyLeaveRequest, $validated) {
            $facultyLeaveRequest->update([
                'status' => $validated['status'],
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'decision_note' => $validated['decision_note'] ?? null,
            ]);

            if ($validated['status'] === 'approved') {
                $sessions = LectureSession::whereHas('subjectAssignment', function ($q) use ($facultyLeaveRequest) {
                    $q->where('faculty_id', $facultyLeaveRequest->faculty_id);
                })
                ->whereBetween('lecture_date', [$facultyLeaveRequest->start_date, $facultyLeaveRequest->end_date])
                ->where('status', '!=', 'cancelled')
                ->get();

                foreach ($sessions as $session) {
                    $session->update([
                        'status' => 'cancelled',
                    ]);
                }
            }

            InAppNotification::create([
                'user_id' => $facultyLeaveRequest->faculty->user_id,
                'title' => "Leave Request " . ucfirst($validated['status']),
                'message' => "Your leave request from {$facultyLeaveRequest->start_date->toDateString()} to {$facultyLeaveRequest->end_date->toDateString()} has been {$validated['status']}.",
                'type' => $validated['status'] === 'approved' ? 'success' : 'warning',
            ]);
        });

        return redirect()
            ->route('leaves.faculty.hod.index')
            ->with('status', "Faculty leave request {$validated['status']} successfully.");
    }
}
