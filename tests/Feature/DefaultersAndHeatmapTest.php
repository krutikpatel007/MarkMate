<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Models\LectureSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultersAndHeatmapTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_hod_can_view_defaulter_management(): void
    {
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);

        $response = $this->actingAs($hod)
            ->get(route('defaulters.index'));

        $response->assertOk();
        $response->assertSeeText('Defaulter Management');
        $response->assertSeeText('Students Below 75% University Threshold');
    }

    public function test_hod_can_view_defaulter_warning_letter(): void
    {
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);

        $student = Student::firstOrFail();

        $response = $this->actingAs($hod)
            ->get(route('defaulters.warning-letter', $student));

        $response->assertOk();
        $response->assertSeeText('OFFICIAL ATTENDANCE WARNING NOTIFICATION');
        $response->assertSeeText('Shreyarth University');
        $response->assertSeeText($student->enrollment_no);
    }

    public function test_hod_can_send_parent_alert(): void
    {
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);

        $student = Student::firstOrFail();

        $response = $this->actingAs($hod)
            ->from(route('defaulters.index'))
            ->post(route('defaulters.parent-alert', $student));

        $response->assertRedirect(route('defaulters.index'));
        $response->assertSessionHas('status');

        // Assert audit log was recorded
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $hod->id,
            'action' => 'send_parent_alert',
            'entity_type' => 'App\Models\Student',
            'entity_id' => $student->id,
        ]);

        // Assert student notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->user_id,
            'title' => 'Parent Defaulter Alert Issued',
        ]);
    }

    public function test_hod_can_view_attendance_compliance_heatmap(): void
    {
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);

        $response = $this->actingAs($hod)
            ->get(route('attendance.heatmap'));

        $response->assertOk();
        $response->assertSeeText('Faculty Submission Compliance');
        $response->assertSeeText('30-Day Submission Compliance Tracker');
    }

    public function test_hod_can_view_heatmap_day_details(): void
    {
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);

        $date = now()->subDay()->toDateString();

        $response = $this->actingAs($hod)
            ->get(route('attendance.heatmap.details', $date));

        $response->assertOk();
        $response->assertSeeText('Compliance Details:');
        $response->assertSeeText('Scheduled Sessions & Submission Log');
    }

    public function test_unauthorized_user_is_blocked_from_defaulters_and_heatmap(): void
    {
        $this->withoutVite();

        $studentUser = User::where('role', 'student')->firstOrFail();
        $studentUser->update(['must_change_password' => false]);

        $student = $studentUser->student;

        // Block from Defaulters Index
        $this->actingAs($studentUser)
            ->get(route('defaulters.index'))
            ->assertStatus(403);

        // Block from Parent Alert Dispatch
        $this->actingAs($studentUser)
            ->post(route('defaulters.parent-alert', $student))
            ->assertStatus(403);

        // Block from Heatmap Index
        $this->actingAs($studentUser)
            ->get(route('attendance.heatmap'))
            ->assertStatus(403);
    }

    public function test_hod_can_send_class_parent_alerts(): void
    {
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);

        $student = Student::firstOrFail();
        $classSection = $student->classSection;

        // Ensure there is at least one subject assignment and conducted session with student marked absent
        $subjectAssignment = \App\Models\SubjectAssignment::where('class_section_id', $classSection->id)->first()
            ?? \App\Models\SubjectAssignment::create([
                'class_section_id' => $classSection->id,
                'subject_id' => \App\Models\Subject::firstOrFail()->id,
                'faculty_id' => \App\Models\Faculty::firstOrFail()->id,
                'status' => 'active',
            ]);

        $session = LectureSession::create([
            'subject_assignment_id' => $subjectAssignment->id,
            'lecture_date' => today(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'conducted',
        ]);

        \App\Models\AttendanceRecord::create([
            'lecture_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'absent',
        ]);

        $response = $this->actingAs($hod)
            ->from(route('dashboard'))
            ->post(route('defaulters.class-parent-alert', $classSection));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status');

        // Assert audit log was recorded
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $hod->id,
            'action' => 'send_parent_alert',
            'entity_type' => 'App\Models\Student',
            'entity_id' => $student->id,
        ]);
    }

    public function test_unauthorized_user_cannot_send_class_parent_alerts(): void
    {
        $this->withoutVite();

        $studentUser = User::where('role', 'student')->firstOrFail();
        $studentUser->update(['must_change_password' => false]);

        $student = $studentUser->student;
        $classSection = $student->classSection;

        $response = $this->actingAs($studentUser)
            ->post(route('defaulters.class-parent-alert', $classSection));

        $response->assertStatus(403);
    }
}
