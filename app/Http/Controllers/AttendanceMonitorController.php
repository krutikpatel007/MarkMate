<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\ClassSection;
use App\Models\LectureSession;
use App\Models\Subject;
use App\Services\LectureSessionGenerator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceMonitorController extends Controller
{
    public function index(Request $request, LectureSessionGenerator $generator): View
    {
        $this->ensureAcademicStaff();
        $generator->generateForDate(today());

        $sessions = LectureSession::query()
            ->with([
                'subjectAssignment.faculty.user',
                'subjectAssignment.subject',
                'subjectAssignment.classSection.program',
                'substituteFaculty.user',
                'attendanceRecords',
            ])
            ->withCount([
                'attendanceRecords',
                'attendanceRecords as present_count' => fn ($query) => $query->where('status', 'present'),
                'attendanceRecords as absent_count' => fn ($query) => $query->where('status', 'absent'),
                'attendanceRecords as leave_count' => fn ($query) => $query->where('status', 'absent_with_leave'),
            ])
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = $request->string('status')->toString();

                if ($status === 'pending') {
                    $query->whereIn('status', ['scheduled', 'pending']);

                    return;
                }

                $query->where('status', $status);
            })
            ->when($request->string('status_group')->toString() === 'pending', fn ($query) => $query->whereIn('status', ['scheduled', 'pending']))
            ->when($request->filled('class_section_id'), function ($query) use ($request) {
                $query->whereHas('subjectAssignment', fn ($q) => $q->where('class_section_id', $request->integer('class_section_id')));
            })
            ->when($request->filled('subject_id'), function ($query) use ($request) {
                $query->whereHas('subjectAssignment', fn ($q) => $q->where('subject_id', $request->integer('subject_id')));
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('lecture_date', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('lecture_date', '<=', $request->input('date_to'));
            })
            ->orderByDesc('lecture_date')
            ->orderBy('start_time')
            ->get();

        $lateSubmissions = $sessions->filter(fn (LectureSession $session) => $this->isLateSubmission($session));

        if ($request->string('view')->toString() === 'late') {
            $sessions = $lateSubmissions->values();
            $lateSubmissions = $sessions;
        }

        return view('attendance.monitor', [
            'sessions' => $sessions,
            'classSections' => ClassSection::query()->orderBy('display_name')->get(),
            'subjects' => $this->subjectsForFilter($request),
            'faculties' => \App\Models\Faculty::with('user')->where('status', 'active')->get()->sortBy('user.name'),
            'selectedStatus' => $request->string('status')->toString(),
            'selectedClassSectionId' => $request->integer('class_section_id') ?: null,
            'selectedSubjectId' => $request->integer('subject_id') ?: null,
            'selectedDateFrom' => $request->input('date_from') ?: null,
            'selectedDateTo' => $request->input('date_to') ?: null,
            'pendingSessions' => $sessions->whereIn('status', ['scheduled', 'pending']),
            'lockedSessions' => $sessions->filter(fn (LectureSession $session) => $session->status === 'locked' || $session->locked_at !== null),
            'cancelledSessions' => $sessions->where('status', 'cancelled'),
            'lateSubmissions' => $lateSubmissions,
            'summary' => $this->summary($sessions, $lateSubmissions),
        ]);
    }

    public function assignSubstitute(Request $request, LectureSession $lectureSession): RedirectResponse
    {
        $this->ensureAcademicStaff();

        $validated = $request->validate([
            'substitute_faculty_id' => ['nullable', 'exists:faculty,id'],
        ]);

        $lectureSession->update([
            'substitute_faculty_id' => $validated['substitute_faculty_id'] ?: null,
        ]);

        return back()->with('status', 'Substitute faculty assigned successfully.');
    }

    public function updateStatus(Request $request, LectureSession $lectureSession): RedirectResponse
    {
        $this->ensureAcademicStaff();

        $validated = $request->validate([
            'status' => ['required', 'in:scheduled,pending,cancelled'],
        ]);

        if ($lectureSession->attendanceRecords()->exists()) {
            return back()->withErrors(['status' => 'Sessions with marked attendance cannot be changed from the monitor.']);
        }

        $lectureSession->update($validated);

        return back()->with('status', 'Lecture session status updated.');
    }

    private function ensureAcademicStaff(): void
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isHod(), 403);
    }

    private function subjectsForFilter(Request $request)
    {
        return Subject::query()
            ->whereHas('subjectAssignments', function ($query) use ($request) {
                $query->when($request->filled('class_section_id'), function ($q) use ($request) {
                    $q->where('class_section_id', $request->integer('class_section_id'));
                });
            })
            ->orderBy('subject_name')
            ->get();
    }

    private function isLateSubmission(LectureSession $session): bool
    {
        if ($session->submitted_at === null || $session->end_time === null) {
            return false;
        }

        $scheduledEnd = Carbon::parse($session->lecture_date->toDateString().' '.substr($session->end_time, 0, 8));

        return $session->submitted_at->greaterThan($scheduledEnd);
    }

    /**
     * @param  Collection<int, LectureSession>  $sessions
     * @param  Collection<int, LectureSession>  $lateSubmissions
     * @return array<string, int>
     */
    private function summary(Collection $sessions, Collection $lateSubmissions): array
    {
        return [
            'total' => $sessions->count(),
            'submitted' => $sessions->whereIn('status', ['conducted', 'locked'])->count(),
            'pending' => $sessions->whereIn('status', ['scheduled', 'pending'])->count(),
            'locked' => $sessions->filter(fn (LectureSession $session) => $session->status === 'locked' || $session->locked_at !== null)->count(),
            'cancelled' => $sessions->where('status', 'cancelled')->count(),
            'late' => $lateSubmissions->count(),
        ];
    }
}
