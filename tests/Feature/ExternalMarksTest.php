<?php

namespace Tests\Feature;

use App\Models\InternalMark;
use App\Models\InternalMarkComponent;
use App\Models\SubjectAssignment;
use App\Models\Student;
use App\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalMarksTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_exam_hod_can_release_external_marks_if_internal_submitted(): void
    {
        $this->withoutVite();

        $assignment = SubjectAssignment::query()
            ->with(['faculty.user', 'classSection.students'])
            ->where('status', 'active')
            ->firstOrFail();

        $student = $assignment->classSection->students->firstOrFail();

        // 1. Initially internal marks are draft, release should fail
        $examHodUser = User::where('username', 'exam_hod')->firstOrFail();
        $examHodUser->update(['must_change_password' => false]);

        $this->actingAs($examHodUser)
            ->post(route('marks.release-external', $assignment))
            ->assertSessionHasErrors('marks');

        // Create internal marks in submitted_to_exam status
        InternalMark::create([
            'subject_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'mid_sem_30' => 30,
            'mid_sem_20' => 20,
            'cie_30' => 25,
            'total_50' => 45,
            'status' => 'submitted_to_exam',
            'submitted_at' => now(),
        ]);

        // 2. Release external marks should now succeed
        $this->actingAs($examHodUser)
            ->post(route('marks.release-external', $assignment))
            ->assertRedirect(route('marks.show', $assignment));

        $this->assertSame('released', $assignment->fresh()->external_marks_status);
    }

    public function test_faculty_can_save_draft_and_submit_external_marks(): void
    {
        $this->withoutVite();

        $assignment = SubjectAssignment::query()
            ->with(['faculty.user', 'classSection.students'])
            ->where('status', 'active')
            ->firstOrFail();

        $student = $assignment->classSection->students->firstOrFail();
        $faculty = $assignment->faculty->user;

        InternalMark::create([
            'subject_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'mid_sem_30' => 30,
            'mid_sem_20' => 20,
            'cie_30' => 25,
            'total_50' => 45,
            'status' => 'submitted_to_exam',
            'submitted_at' => now(),
        ]);

        // Mark assignment as released
        $assignment->update(['external_marks_status' => 'released']);

        // 1. Faculty saves external marks draft
        $this->actingAs($faculty)
            ->post(route('marks.store-external', $assignment), [
                'external_marks' => [
                    $student->id => '42',
                ]
            ])
            ->assertRedirect(route('marks.show', $assignment));

        $markRecord = InternalMark::where('subject_assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $this->assertEquals(42.00, $markRecord->external_50);
        $this->assertEquals(87.00, $markRecord->total_100);
        $this->assertSame('released', $assignment->fresh()->external_marks_status);

        // 2. Faculty submits external marks final
        $this->actingAs($faculty)
            ->post(route('marks.submit-external', $assignment), [
                'external_marks' => [
                    $student->id => '43.50',
                ]
            ])
            ->assertRedirect(route('marks.show', $assignment));

        $markRecord = $markRecord->fresh();
        $this->assertEquals(43.50, $markRecord->external_50);
        $this->assertEquals(88.50, $markRecord->total_100);
        $this->assertSame('submitted', $assignment->fresh()->external_marks_status);

        // 3. Trying to edit after submission should fail (returns 403 because status is submitted)
        $this->actingAs($faculty)
            ->post(route('marks.store-external', $assignment), [
                'external_marks' => [
                    $student->id => '45',
                ]
            ])
            ->assertStatus(403);
    }

    public function test_student_scorecard_displays_external_marks_only_when_submitted(): void
    {
        $this->withoutVite();

        $assignment = SubjectAssignment::query()
            ->with(['subject', 'faculty.user', 'classSection.students.user'])
            ->where('status', 'active')
            ->firstOrFail();

        $studentUser = $assignment->classSection->students->firstOrFail()->user;
        $studentUser->update(['must_change_password' => false]);
        $student = $studentUser->student;

        $mark = InternalMark::create([
            'subject_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'mid_sem_30' => 30,
            'mid_sem_20' => 20,
            'cie_30' => 25,
            'total_50' => 45,
            'external_50' => 40,
            'total_100' => 85,
            'status' => 'submitted_to_exam',
            'submitted_at' => now(),
        ]);

        // Initially results are locked
        $this->actingAs($studentUser)
            ->get(route('marks.student'))
            ->assertOk()
            ->assertSeeText('Results Awaiting Declaration');

        // Release results for the class section
        $student->classSection->update(['results_released' => true]);

        // 1. When external status is released, student doesn't see external marks or grand total / 100
        $assignment->update(['external_marks_status' => 'released']);

        $this->actingAs($studentUser)
            ->get(route('marks.student'))
            ->assertOk()
            ->assertSeeText('45') // Shows CIE out of 50
            ->assertDontSeeText('85.00') // Does not show Total 100
            ->assertDontSeeText('40.00'); // Does not show External mark 40.00

        // 2. When external status is submitted, student sees external marks and grand total / 100
        $assignment->update(['external_marks_status' => 'submitted']);

        $this->actingAs($studentUser)
            ->get(route('marks.student'))
            ->assertOk()
            ->assertSeeText('85.00') // Shows Total 100
            ->assertSeeText('40.00') // Shows External mark 40.00
            ->assertSeeText('End Sem (50)');
    }

    public function test_csv_export_includes_external_marks_columns_when_enabled(): void
    {
        $assignment = SubjectAssignment::query()
            ->with(['subject', 'faculty.user', 'classSection.students'])
            ->where('status', 'active')
            ->firstOrFail();

        $student = $assignment->classSection->students->firstOrFail();
        $faculty = $assignment->faculty->user;

        // Configure component
        InternalMarkComponent::create([
            'subject_assignment_id' => $assignment->id,
            'name' => 'Assignment 1',
            'max_marks' => 30,
        ]);

        InternalMark::create([
            'subject_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'mid_sem_30' => 30,
            'mid_sem_20' => 20,
            'cie_30' => 25,
            'total_50' => 45,
            'external_50' => 40,
            'total_100' => 85,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // 1. Without release, CSV doesn't have external headers
        $assignment->update(['external_marks_status' => 'not_released']);
        $response = $this->actingAs($faculty)
            ->get(route('marks.export', $assignment))
            ->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringNotContainsString('External Marks (50)', $csv);

        // 2. With release/submitted, CSV contains external headers
        $assignment->update(['external_marks_status' => 'submitted']);
        $response = $this->actingAs($faculty)
            ->get(route('marks.export', $assignment))
            ->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('External Marks (50)', $csv);
        $this->assertStringContainsString('Total Marks (100)', $csv);
    }
}
