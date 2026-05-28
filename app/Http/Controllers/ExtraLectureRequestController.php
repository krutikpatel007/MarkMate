<?php

namespace App\Http\Controllers;

use App\Models\ExtraLectureRequest;
use App\Models\InAppNotification;
use App\Models\LectureSession;
use App\Models\SubjectAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExtraLectureRequestController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $query = ExtraLectureRequest::with([
            'faculty.user',
            'subjectAssignment.subject',
            'subjectAssignment.classSection',
            'approver',
        ])->latest();

        if ($user->isFaculty()) {
            $query->where('faculty_id', $user->facultyProfile->id);
        } else {
            abort_unless($user->isAdmin() || $user->isHod(), 403);
        }

        return view('extra-lectures.index', [
            'requests' => $query->get(),
        ]);
    }

    public function create(): View
    {
        abort_unless(Auth::user()->isFaculty(), 403);

        return view('extra-lectures.create', [
            'assignments' => SubjectAssignment::with(['subject', 'classSection'])
                ->where('faculty_id', Auth::user()->facultyProfile->id)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isFaculty(), 403);

        $validated = $request->validate([
            'subject_assignment_id' => ['required', 'exists:subject_assignments,id'],
            'requested_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'session_type' => ['required', Rule::in(['extra', 'remedial'])],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $assignment = SubjectAssignment::where('id', $validated['subject_assignment_id'])
            ->where('faculty_id', $request->user()->facultyProfile->id)
            ->firstOrFail();

        ExtraLectureRequest::create([
            ...$validated,
            'faculty_id' => $assignment->faculty_id,
            'approval_status' => 'pending',
        ]);

        return redirect()
            ->route('extra-lectures.index')
            ->with('status', 'Extra/remedial lecture request sent for HOD approval.');
    }

    public function decide(Request $request, ExtraLectureRequest $extraLectureRequest): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isHod(), 403);

        $validated = $request->validate([
            'approval_status' => ['required', Rule::in(['approved', 'rejected'])],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($extraLectureRequest->approval_status === 'pending', 422, 'This request has already been decided.');

        DB::transaction(function () use ($request, $extraLectureRequest, $validated) {
            $extraLectureRequest->update([
                'approval_status' => $validated['approval_status'],
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'decision_note' => $validated['decision_note'] ?? null,
            ]);

            if ($validated['approval_status'] === 'approved') {
                LectureSession::create([
                    'subject_assignment_id' => $extraLectureRequest->subject_assignment_id,
                    'extra_lecture_request_id' => $extraLectureRequest->id,
                    'lecture_date' => $extraLectureRequest->requested_date,
                    'start_time' => $extraLectureRequest->start_time,
                    'end_time' => $extraLectureRequest->end_time,
                    'session_type' => $extraLectureRequest->session_type,
                    'status' => 'scheduled',
                ]);
            }

            InAppNotification::create([
                'user_id' => $extraLectureRequest->faculty->user_id,
                'title' => 'Extra lecture request '.$validated['approval_status'],
                'message' => 'Your '.$extraLectureRequest->session_type.' lecture request has been '.$validated['approval_status'].'.',
                'type' => $validated['approval_status'] === 'approved' ? 'success' : 'warning',
            ]);
        });

        return redirect()
            ->route('extra-lectures.index')
            ->with('status', 'Request '.$validated['approval_status'].' successfully.');
    }
}
