<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Models\Faculty;
use App\Models\SubjectAssignment;
use App\Models\InternalMark;
use App\Models\ReEvaluationRequest;
use App\Models\ExamWaiver;
use App\Models\AttendanceRecord;
use App\Models\LectureSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamDepartmentAdvancedTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_student_hall_ticket_locked_when_attendance_below_75(): void
    {
        $this->withoutVite();

        $student = Student::with('user')->firstOrFail();
        $studentUser = $student->user;
        $studentUser->must_change_password = false;
        $studentUser->save();

        // Create low attendance records (e.g. 1 present, 4 absent = 20%)
        $assignment = SubjectAssignment::firstOrFail();
        
        for ($i = 0; $i < 5; $i++) {
            $session = LectureSession::create([
                'subject_assignment_id' => $assignment->id,
                'lecture_date' => now()->subDays($i),
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'status' => 'locked',
            ]);

            AttendanceRecord::create([
                'lecture_session_id' => $session->id,
                'student_id' => $student->id,
                'status' => $i === 0 ? 'present' : 'absent',
            ]);
        }

        // Student tries to load hall ticket page
        $response = $this->actingAs($studentUser)
            ->get(route('student.hall-ticket.show'))
            ->assertOk()
            ->assertSeeText('Hall Ticket Locked')
            ->assertSeeText('Blocked');

        // Student tries to download hall ticket PDF (should be blocked with 403)
        $response = $this->actingAs($studentUser)
            ->get(route('student.hall-ticket.download'))
            ->assertStatus(403);
    }

    public function test_coordinator_can_grant_waiver_to_unlock_hall_ticket(): void
    {
        $this->withoutVite();

        $student = Student::with('user')->firstOrFail();
        $studentUser = $student->user;
        $studentUser->must_change_password = false;
        $studentUser->save();
        $examHod = User::where('username', 'exam_hod')->firstOrFail();

        // Grant waiver
        $response = $this->actingAs($examHod)
            ->post(route('exam.hall-tickets.store-waiver', $student->id), [
                'reason' => 'Approved sports participation override',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('exam_waivers', [
            'student_id' => $student->id,
            'reason' => 'Approved sports participation override',
        ]);

        // Student should now be eligible
        $response = $this->actingAs($studentUser)
            ->get(route('student.hall-ticket.show'))
            ->assertOk()
            ->assertSeeText('Clearance Certificate Issued');

        // Student can now download hall ticket
        $response = $this->actingAs($studentUser)
            ->get(route('student.hall-ticket.download'))
            ->assertOk()
            ->assertSeeText('End-Semester Examinations Hall Ticket');
    }

    public function test_impartiality_check_blocks_original_faculty_assignment(): void
    {
        $this->withoutVite();

        $student = Student::with('user')->firstOrFail();
        $examHod = User::where('username', 'exam_hod')->firstOrFail();
        
        $assignment = SubjectAssignment::with('faculty.user')->firstOrFail();
        $originalFaculty = $assignment->faculty->user;

        // Set up finalized internal marks
        $markRecord = InternalMark::create([
            'subject_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'cie_30' => 15,
            'mid_sem_20' => 10,
            'total_50' => 25,
            'status' => 'submitted_to_exam',
            'marked_by' => $originalFaculty->id,
        ]);

        // Student applies for recheck
        $recheckRequest = ReEvaluationRequest::create([
            'student_id' => $student->id,
            'subject_assignment_id' => $assignment->id,
            'type' => 'rechecking',
            'status' => 'requested',
            'original_marks' => 25,
        ]);

        // Coordinator attempts to assign scrutiny back to original faculty member (should fail check)
        $response = $this->actingAs($examHod)
            ->post(route('exam.scrutiny.assign', $recheckRequest->id), [
                'assigned_to' => $originalFaculty->id,
            ]);

        $response->assertSessionHasErrors('assigned_to');
        
        // Assert it was NOT assigned
        $this->assertEquals('requested', $recheckRequest->fresh()->status);
    }

    public function test_complete_scrutiny_approval_workflow_and_marks_sync(): void
    {
        $this->withoutVite();

        $student = Student::with('user')->firstOrFail();
        $examHod = User::where('username', 'exam_hod')->firstOrFail();
        
        $assignment = SubjectAssignment::firstOrFail();
        
        // Find another senior faculty member to assign scrutiny
        $reviewerFaculty = Faculty::with('user')->where('id', '!=', $assignment->faculty_id)->firstOrFail();
        $reviewer = $reviewerFaculty->user;

        // Create finalized marks
        $markRecord = InternalMark::create([
            'subject_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'cie_30' => 10,
            'mid_sem_20' => 8,
            'total_50' => 18, // failing score
            'status' => 'submitted_to_exam',
        ]);

        // Create request
        $recheckRequest = ReEvaluationRequest::create([
            'student_id' => $student->id,
            'subject_assignment_id' => $assignment->id,
            'type' => 'recount',
            'status' => 'requested',
            'original_marks' => 18,
        ]);

        // 1. Coordinator assigns evaluator
        $response = $this->actingAs($examHod)
            ->post(route('exam.scrutiny.assign', $recheckRequest->id), [
                'assigned_to' => $reviewer->id,
            ])
            ->assertRedirect();

        $this->assertEquals('assigned', $recheckRequest->fresh()->status);

        // 2. Evaluator audits and enters revised marks
        $response = $this->actingAs($reviewer)
            ->post(route('faculty.scrutiny.submit', $recheckRequest->id), [
                'revised_marks' => 20, // passes now
                'evaluator_remarks' => 'Mathematical recount error found in CIE sum.',
            ])
            ->assertRedirect(route('faculty.scrutiny.index'));

        $this->assertEquals('scrutinized', $recheckRequest->fresh()->status);
        $this->assertEquals(20.00, $recheckRequest->fresh()->revised_marks);

        // 3. Coordinator approves scrutiny (updates student gradesheet)
        $response = $this->actingAs($examHod)
            ->post(route('exam.scrutiny.approve', $recheckRequest->id), [
                'coordinator_remarks' => 'Scrutiny verified and approved.',
            ])
            ->assertRedirect();

        $this->assertEquals('completed', $recheckRequest->fresh()->status);
        
        // Assert student actual mark table updated instantly!
        $this->assertEquals(20.00, $markRecord->fresh()->total_50);
        $this->assertEquals($reviewer->id, $markRecord->fresh()->marked_by);
    }
}
