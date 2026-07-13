<?php

namespace App\Http\Controllers;

use App\Models\SubjectAssignment;
use App\Models\FacultyFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentFeedbackController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()->isStudent(), 403);

        $student = Auth::user()->student;

        // Fetch active subject assignments for the student's class section
        $assignments = SubjectAssignment::with(['subject', 'faculty.user'])
            ->where('class_section_id', $student->class_section_id)
            ->where('status', 'active')
            ->get();

        // Feedbacks already submitted by this student
        $submittedFeedbackIds = FacultyFeedback::where('student_id', $student->id)
            ->pluck('subject_assignment_id')
            ->all();

        return view('hr.feedback', [
            'assignments' => $assignments,
            'submittedFeedbackIds' => $submittedFeedbackIds,
        ]);
    }

    public function store(Request $request, SubjectAssignment $assignment)
    {
        abort_unless(Auth::user()->isStudent(), 403);

        $student = Auth::user()->student;
        
        // Assert student is in the class section for this assignment
        abort_unless($student->class_section_id === $assignment->class_section_id, 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comments' => ['nullable', 'string', 'max:500'],
        ]);

        FacultyFeedback::updateOrCreate(
            [
                'student_id' => $student->id,
                'subject_assignment_id' => $assignment->id,
            ],
            [
                'rating' => $validated['rating'],
                'comments' => $validated['comments'] ?? null,
            ]
        );

        return redirect()->back()->with('status', 'Thank you! Your feedback has been submitted.');
    }
}
