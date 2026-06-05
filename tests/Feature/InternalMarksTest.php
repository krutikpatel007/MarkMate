<?php

namespace Tests\Feature;

use App\Models\InternalMark;
use App\Models\InternalMarkComponent;
use App\Models\SubjectAssignment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalMarksTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_faculty_can_configure_components_with_exact_30_marks(): void
    {
        $this->withoutVite();

        $assignment = SubjectAssignment::query()
            ->with('faculty.user')
            ->where('status', 'active')
            ->firstOrFail();

        $faculty = $assignment->faculty->user;

        $response = $this->actingAs($faculty)
            ->get(route('marks.configure.create', $assignment))
            ->assertOk()
            ->assertSeeText('Configure CIE Components');

        $response = $this->actingAs($faculty)
            ->post(route('marks.configure.store', $assignment), [
                'components' => [
                    ['name' => 'Assignment 1', 'max_marks' => '10'],
                    ['name' => 'Assignment 2', 'max_marks' => '10'],
                    ['name' => 'Quiz 1', 'max_marks' => '5'],
                    ['name' => 'Attendance', 'max_marks' => '5'],
                ]
            ])
            ->assertRedirect(route('marks.show', $assignment));

        $this->assertDatabaseHas('internal_marks_components', [
            'subject_assignment_id' => $assignment->id,
            'name' => 'Assignment 1',
            'max_marks' => 10,
        ]);

        $this->assertDatabaseCount('internal_marks_components', 4);
    }

    public function test_faculty_cannot_configure_components_with_invalid_marks_sum(): void
    {
        $assignment = SubjectAssignment::query()
            ->with('faculty.user')
            ->where('status', 'active')
            ->firstOrFail();

        $faculty = $assignment->faculty->user;

        // Sum is 25 instead of 30
        $this->actingAs($faculty)
            ->post(route('marks.configure.store', $assignment), [
                'components' => [
                    ['name' => 'Assignment 1', 'max_marks' => '10'],
                    ['name' => 'Assignment 2', 'max_marks' => '10'],
                    ['name' => 'Quiz 1', 'max_marks' => '5'],
                ]
            ])
            ->assertSessionHasErrors('components');

        $this->assertDatabaseCount('internal_marks_components', 0);
    }

    public function test_faculty_can_save_marks_draft_and_final_submit(): void
    {
        $this->withoutVite();

        $assignment = SubjectAssignment::query()
            ->with(['faculty.user', 'classSection.students'])
            ->where('status', 'active')
            ->firstOrFail();

        $faculty = $assignment->faculty->user;

        // Configure first
        InternalMarkComponent::create([
            'subject_assignment_id' => $assignment->id,
            'name' => 'Assignment 1',
            'max_marks' => 10,
        ]);
        $comp2 = InternalMarkComponent::create([
            'subject_assignment_id' => $assignment->id,
            'name' => 'Class Test',
            'max_marks' => 20,
        ]);

        $student = $assignment->classSection->students->firstOrFail();

        // 1. Save Draft
        $this->actingAs($faculty)
            ->post(route('marks.store', $assignment), [
                'mid_sem_30' => [
                    $student->id => '21', // Scaled Mid Sem: (21 / 30) * 20 = 14
                ],
                'comp_marks' => [
                    $student->id => [
                        $comp2->id => '16', // CIE = 16
                    ]
                ]
            ])
            ->assertRedirect(route('marks.show', $assignment));

        $this->assertDatabaseHas('internal_marks', [
            'subject_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'mid_sem_30' => 21.00,
            'mid_sem_20' => 14.00,
            'cie_30' => 16.00,
            'total_50' => 30.00,
            'status' => 'draft',
        ]);

        // 2. Final Submit
        $this->actingAs($faculty)
            ->post(route('marks.submit', $assignment))
            ->assertRedirect(route('marks.show', $assignment));

        $this->assertDatabaseHas('internal_marks', [
            'subject_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'status' => 'submitted_to_hod',
        ]);

        // 3. Trying to edit after submit should fail
        $this->actingAs($faculty)
            ->post(route('marks.store', $assignment), [
                'mid_sem_30' => [
                    $student->id => '30',
                ]
            ])
            ->assertSessionHasErrors('marks');
    }

    public function test_hod_can_unlock_submitted_marks(): void
    {
        $hod = User::where('role', 'hod')->firstOrFail();
        $assignment = SubjectAssignment::query()
            ->with(['faculty.user', 'classSection.students'])
            ->where('status', 'active')
            ->firstOrFail();

        $student = $assignment->classSection->students->firstOrFail();

        $mark = InternalMark::create([
            'subject_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'status' => 'submitted_to_hod',
            'submitted_at' => now(),
        ]);

        $this->actingAs($hod)
            ->post(route('marks.unlock', $assignment))
            ->assertRedirect(route('marks.show', $assignment));

        $this->assertSame('draft', $mark->fresh()->status);
        $this->assertNull($mark->fresh()->submitted_at);
    }

    public function test_student_can_view_scorecard_once_submitted(): void
    {
        $this->withoutVite();

        $assignment = SubjectAssignment::query()
            ->with(['subject', 'faculty.user', 'classSection.students.user'])
            ->where('status', 'active')
            ->firstOrFail();

        $studentUser = $assignment->classSection->students->firstOrFail()->user;
        $studentUser->update(['must_change_password' => false]);
        $student = $studentUser->student;

        // Configure component
        $comp = InternalMarkComponent::create([
            'subject_assignment_id' => $assignment->id,
            'name' => 'Assignment 1',
            'max_marks' => 30,
        ]);

        // Prior to submission, student scorecard shows empty state
        $this->actingAs($studentUser)
            ->get(route('marks.student'))
            ->assertOk()
            ->assertSeeText('Scorecard Not Available');

        // Submit the marks
        $mark = InternalMark::create([
            'subject_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'mid_sem_30' => 30,
            'mid_sem_20' => 20,
            'cie_30' => 25,
            'total_50' => 45,
            'status' => 'submitted_to_exam',
            'submitted_at' => now(),
        ]);

        // Student can now view their scorecard with details
        $this->actingAs($studentUser)
            ->get(route('marks.student'))
            ->assertOk()
            ->assertDontSeeText('Scorecard Not Available')
            ->assertSeeText($assignment->subject->subject_name)
            ->assertSeeText('45');
    }

    public function test_faculty_and_hod_can_export_marks_csv(): void
    {
        $hod = User::where('role', 'hod')->firstOrFail();
        $assignment = SubjectAssignment::query()
            ->with(['subject', 'faculty.user', 'classSection.students'])
            ->where('status', 'active')
            ->firstOrFail();

        $faculty = $assignment->faculty->user;

        // Configure component
        $comp = InternalMarkComponent::create([
            'subject_assignment_id' => $assignment->id,
            'name' => 'Assignment 1',
            'max_marks' => 30,
        ]);

        $student = $assignment->classSection->students->firstOrFail();

        $mark = InternalMark::create([
            'subject_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'mid_sem_30' => 30,
            'mid_sem_20' => 20,
            'cie_30' => 25,
            'total_50' => 45,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Faculty can export
        $response = $this->actingAs($faculty)
            ->get(route('marks.export', $assignment))
            ->assertOk();
        
        $csv = $response->streamedContent();
        $this->assertStringContainsString('"Roll No","Enrollment No","Student Name","Mid Sem Exam (30)","Mid Sem Exam (20)","Assignment 1 (30)","CIE Total (30)","Total Marks (50)"', $csv);

        // HOD can export
        $response = $this->actingAs($hod)
            ->get(route('marks.export', $assignment))
            ->assertOk();
    }
}
