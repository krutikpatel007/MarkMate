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
}
