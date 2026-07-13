<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\FacultySalaryConfig;
use App\Models\FacultyPayslip;
use App\Models\FacultyAppraisal;
use App\Models\FacultyFeedback;
use App\Models\SubjectAssignment;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyHrManagementTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_admin_can_access_hr_dashboard(): void
    {
        $this->withoutVite();

        $admin = User::where('role', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get(route('hr.dashboard'));

        $response->assertOk();
        $response->assertSeeText('HR & Faculty Management');
        $response->assertSeeText('Faculty Directory');
    }

    public function test_admin_can_configure_salary_and_generate_payslip(): void
    {
        $this->withoutVite();

        $admin = User::where('role', 'admin')->firstOrFail();
        $faculty = Faculty::firstOrFail();

        // 1. Configure salary
        $response = $this->actingAs($admin)
            ->post(route('hr.faculty.salary.store', $faculty->id), [
                'basic_pay' => 50000.00,
                'hra' => 10000.00,
                'da' => 5000.00,
                'special_allowance' => 2000.00,
                'deductions' => 3000.00,
            ]);

        $response->assertRedirect();
        
        $salaryConfig = FacultySalaryConfig::where('faculty_id', $faculty->id)->firstOrFail();
        $this->assertEquals(50000.00, $salaryConfig->basic_pay);

        // 2. Generate payslip
        $response = $this->actingAs($admin)
            ->post(route('hr.faculty.payslip.store', $faculty->id), [
                'month' => 7,
                'year' => 2026,
            ]);

        $response->assertRedirect();

        $payslip = FacultyPayslip::where('faculty_id', $faculty->id)->where('month', 7)->where('year', 2026)->firstOrFail();
        // net_salary = 50000 + 10000 + 5000 + 2000 - 3000 = 64000
        $this->assertEquals(64000.00, $payslip->net_salary);

        // 3. Prevent duplicate payslip
        $response = $this->actingAs($admin)
            ->post(route('hr.faculty.payslip.store', $faculty->id), [
                'month' => 7,
                'year' => 2026,
            ]);

        $response->assertSessionHasErrors();
    }

    public function test_admin_can_submit_performance_appraisal(): void
    {
        $this->withoutVite();

        $admin = User::where('role', 'admin')->firstOrFail();
        $faculty = Faculty::firstOrFail();

        $response = $this->actingAs($admin)
            ->post(route('hr.faculty.appraisal.store', $faculty->id), [
                'academic_year' => '2026-27',
                'overall_rating' => 4.5,
                'score_teaching' => 90,
                'score_research' => 85,
                'score_administrative' => 80,
                'review_comments' => 'Outstanding academic contributions.',
            ]);

        $response->assertRedirect();

        $appraisal = FacultyAppraisal::where('faculty_id', $faculty->id)->firstOrFail();
        $this->assertEquals(4.5, $appraisal->overall_rating);
        $this->assertEquals(90, $appraisal->score_teaching);
        $this->assertEquals('Outstanding academic contributions.', $appraisal->review_comments);
    }

    public function test_student_can_submit_course_feedback(): void
    {
        $this->withoutVite();

        // Find a student and an active subject assignment in their class section
        $studentUser = User::where('role', 'student')->firstOrFail();
        $studentUser->update(['must_change_password' => false]);
        $student = $studentUser->student;
        $assignment = SubjectAssignment::where('class_section_id', $student->class_section_id)->firstOrFail();

        $response = $this->actingAs($studentUser)
            ->get(route('student.feedback.index'));

        $response->assertOk();
        $response->assertSeeText($assignment->subject->subject_name);

        $response = $this->actingAs($studentUser)
            ->post(route('student.feedback.store', $assignment->id), [
                'rating' => 5,
                'comments' => 'Excellent teaching style and notes.',
            ]);

        $response->assertRedirect();

        $feedback = FacultyFeedback::where('student_id', $student->id)->where('subject_assignment_id', $assignment->id)->firstOrFail();
        $this->assertEquals(5, $feedback->rating);
        $this->assertEquals('Excellent teaching style and notes.', $feedback->comments);
    }

    public function test_unauthorized_user_cannot_access_hr_dashboard(): void
    {
        $studentUser = User::where('role', 'student')->firstOrFail();
        $studentUser->update(['must_change_password' => false]);

        $this->actingAs($studentUser)
            ->get(route('hr.dashboard'))
            ->assertForbidden();
    }

    public function test_weekly_load_hours_calculates_correctly_with_overlapping_slots(): void
    {
        $faculty = Faculty::firstOrFail();

        // Let's create an active timetable slot
        $assignment1 = SubjectAssignment::create([
            'faculty_id' => $faculty->id,
            'subject_id' => \App\Models\Subject::firstOrFail()->id,
            'class_section_id' => \App\Models\ClassSection::firstOrFail()->id,
            'academic_year' => '2026-27',
            'status' => 'active',
        ]);

        // Clear existing active slots first to test in isolation
        Timetable::whereIn('subject_assignment_id', $faculty->subjectAssignments->pluck('id'))->update(['status' => 'inactive']);

        // Slot 1: Monday 09:00 to 10:00 (1 hour)
        Timetable::create([
            'subject_assignment_id' => $assignment1->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'lecture_no' => 1,
            'status' => 'active',
        ]);

        // Slot 2: Monday 09:00 to 10:00 (overlapping - merged class BCA A + BCA B)
        Timetable::create([
            'subject_assignment_id' => $assignment1->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'lecture_no' => 1,
            'status' => 'active',
        ]);

        // Slot 3: Monday 09:30 to 11:00 (partially overlapping - extends to 11:00)
        Timetable::create([
            'subject_assignment_id' => $assignment1->id,
            'day_of_week' => 1,
            'start_time' => '09:30:00',
            'end_time' => '11:00:00',
            'lecture_no' => 2,
            'status' => 'active',
        ]);

        // Slot 4: Monday 12:00 to 13:00 (non-overlapping)
        Timetable::create([
            'subject_assignment_id' => $assignment1->id,
            'day_of_week' => 1,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'lecture_no' => 3,
            'status' => 'active',
        ]);

        // Total expected load:
        // Segment 1 (09:00 to 11:00) = 2 hours
        // Segment 2 (12:00 to 13:00) = 1 hour
        // Total = 3 hours
        $this->assertEquals(3.00, $faculty->fresh()->weeklyLoadHours());
    }

    public function test_hr_role_user_can_access_hr_dashboard_and_mutate_configs(): void
    {
        $this->withoutVite();

        // 1. Create a user with role 'hr'
        $hrUser = User::create([
            'name' => 'HR Staff',
            'username' => 'hr_staff_test',
            'email' => 'hr.staff.test@scsa.local',
            'password' => \Illuminate\Support\Facades\Hash::make('hr123'),
            'role' => 'hr',
            'must_change_password' => false,
        ]);

        $faculty = Faculty::firstOrFail();

        // 2. Access dashboard
        $response = $this->actingAs($hrUser)
            ->get(route('hr.dashboard'));

        $response->assertOk();
        $response->assertSeeText('HR & Faculty Management');

        // 3. Configure salary config as HR
        $response = $this->actingAs($hrUser)
            ->post(route('hr.faculty.salary.store', $faculty->id), [
                'basic_pay' => 60000.00,
                'hra' => 12000.00,
                'da' => 6000.00,
                'special_allowance' => 2000.00,
                'deductions' => 4000.00,
            ]);

        $response->assertRedirect();
        $salaryConfig = FacultySalaryConfig::where('faculty_id', $faculty->id)->firstOrFail();
        $this->assertEquals(60000.00, $salaryConfig->basic_pay);
    }

    public function test_hr_user_can_access_dashboard_with_statistics(): void
    {
        $this->withoutVite();

        $hrUser = User::create([
            'name' => 'HR Staff Test 2',
            'username' => 'hr_staff_test_2',
            'email' => 'hr.staff.test.2@scsa.local',
            'password' => \Illuminate\Support\Facades\Hash::make('hr123'),
            'role' => 'hr',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($hrUser)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Faculty Weekly Load Overview');
        $response->assertSeeText('Pending Faculty Leave Requests');
        $response->assertSeeText('Recent Payslips Generated');
    }
}
