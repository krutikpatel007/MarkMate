<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Faculty;
use App\Models\SubjectAssignment;
use App\Models\ReEvaluationRequest;
use App\Models\InternalMark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReEvaluationController extends Controller
{
    public function studentIndex()
    {
        $user = Auth::user();
        abort_unless($user->isStudent(), 403);

        $student = Student::where('user_id', $user->id)->firstOrFail();

        // Get finalized internal marks
        $marks = InternalMark::with([
            'subjectAssignment.subject',
            'subjectAssignment.faculty.user'
        ])
        ->where('student_id', $student->id)
        ->whereIn('status', ['submitted_to_exam', 'submitted'])
        ->get();

        // Load past/active requests
        $requests = ReEvaluationRequest::with('subjectAssignment.subject')
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('subject_assignment_id');

        return view('student.re_evaluation.index', compact('marks', 'requests', 'student'));
    }

    public function studentStore(Request $request, SubjectAssignment $subjectAssignment)
    {
        $user = Auth::user();
        abort_unless($user->isStudent(), 403);

        $student = Student::where('user_id', $user->id)->firstOrFail();

        // Verify marks exist and are finalized
        $markRecord = InternalMark::where('student_id', $student->id)
            ->where('subject_assignment_id', $subjectAssignment->id)
            ->first();

        if (!$markRecord || !in_array($markRecord->status, ['submitted_to_exam', 'submitted'], true)) {
            return back()->withErrors(['marks' => 'Internal marks for this subject are not finalized yet.']);
        }

        // Verify no duplicate request
        $exists = ReEvaluationRequest::where('student_id', $student->id)
            ->where('subject_assignment_id', $subjectAssignment->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['marks' => 'A recheck/re-evaluation request has already been submitted for this subject.']);
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:recount,rechecking'],
            'student_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        ReEvaluationRequest::create([
            'student_id' => $student->id,
            'subject_assignment_id' => $subjectAssignment->id,
            'type' => $validated['type'],
            'status' => 'requested',
            'original_marks' => $markRecord->total_50,
            'student_remarks' => $validated['student_remarks'],
        ]);

        return redirect()->route('student.re-evaluation.index')->with('status', 'Re-evaluation request submitted successfully.');
    }

    public function coordinatorIndex(Request $request)
    {
        $user = Auth::user();
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';
        abort_unless($user->isAdmin() || $user->isCoe() || ($user->isHod() && $isExamDept), 403);

        $requests = ReEvaluationRequest::with([
            'student.user',
            'student.classSection',
            'subjectAssignment.subject',
            'subjectAssignment.faculty.user',
            'evaluator'
        ])->latest()->get();

        // Load all faculty list for assigning (except in the assign step itself we filter)
        $faculties = Faculty::with('user')->get();

        return view('exam.scrutiny.index', compact('requests', 'faculties'));
    }

    public function coordinatorAssign(Request $request, ReEvaluationRequest $requestItem)
    {
        $user = Auth::user();
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';
        abort_unless($user->isAdmin() || $user->isCoe() || ($user->isHod() && $isExamDept), 403);

        $validated = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        $evaluatorFaculty = Faculty::where('user_id', $validated['assigned_to'])->firstOrFail();

        // Impartiality Check: Evaluator must NOT be the original marker
        if ((int)$evaluatorFaculty->id === (int)$requestItem->subjectAssignment->faculty_id) {
            throw ValidationException::withMessages([
                'assigned_to' => ['Impartiality Check: You cannot assign the scrutiny to the original subject faculty member.'],
            ]);
        }

        $requestItem->update([
            'assigned_to' => $validated['assigned_to'],
            'status' => 'assigned',
        ]);

        return back()->with('status', 'Paper scrutiny evaluator assigned successfully.');
    }

    public function facultyIndex()
    {
        $user = Auth::user();
        abort_unless($user->isFaculty() || $user->isHod(), 403);

        $requests = ReEvaluationRequest::with([
            'student.user',
            'student.classSection',
            'subjectAssignment.subject'
        ])
        ->where('assigned_to', $user->id)
        ->latest()
        ->get();

        return view('faculty.scrutiny.index', compact('requests'));
    }

    public function facultyScrutinize(Request $request, ReEvaluationRequest $requestItem)
    {
        $user = Auth::user();
        abort_unless((int)$requestItem->assigned_to === (int)$user->id, 403);

        $validated = $request->validate([
            'revised_marks' => ['required', 'numeric', 'min:0', 'max:50'],
            'evaluator_remarks' => ['required', 'string', 'max:1000'],
        ]);

        $requestItem->update([
            'revised_marks' => $validated['revised_marks'],
            'evaluator_remarks' => $validated['evaluator_remarks'],
            're_evaluated_at' => now(),
            'status' => 'scrutinized',
        ]);

        return redirect()->route('faculty.scrutiny.index')->with('status', 'Scrutiny marks submitted successfully for HOD approval.');
    }

    public function coordinatorApprove(Request $request, ReEvaluationRequest $requestItem)
    {
        $user = Auth::user();
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';
        abort_unless($user->isAdmin() || $user->isCoe() || ($user->isHod() && $isExamDept), 403);

        $validated = $request->validate([
            'coordinator_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($requestItem, $user, $validated) {
            // Update live student mark
            InternalMark::where('student_id', $requestItem->student_id)
                ->where('subject_assignment_id', $requestItem->subject_assignment_id)
                ->update([
                    'total_50' => $requestItem->revised_marks,
                    'marked_by' => $requestItem->assigned_to, // mark as re-evaluated by reviewer
                ]);

            $requestItem->update([
                'approved_by' => $user->id,
                'coordinator_remarks' => $validated['coordinator_remarks'],
                'approved_at' => now(),
                'status' => 'completed',
            ]);
        });

        return back()->with('status', 'Marks re-evaluation approved and synced to student gradesheet.');
    }

    public function coordinatorReject(Request $request, ReEvaluationRequest $requestItem)
    {
        $user = Auth::user();
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';
        abort_unless($user->isAdmin() || $user->isCoe() || ($user->isHod() && $isExamDept), 403);

        $validated = $request->validate([
            'coordinator_remarks' => ['required', 'string', 'max:1000'],
        ]);

        $requestItem->update([
            'approved_by' => $user->id,
            'coordinator_remarks' => $validated['coordinator_remarks'] . ' (REJECTED)',
            'approved_at' => now(),
            'status' => 'completed',
        ]);

        return back()->with('status', 'Scrutiny paper adjustments rejected. Original marks kept intact.');
    }
}
