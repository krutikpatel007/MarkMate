<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LectureSession;
use App\Models\User;
use App\Models\Student;
use App\Models\SubjectAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PastAttendanceMarkingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_hod_can_toggle_department_past_attendance_permission(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $dept = $hod->facultyProfile->department;

        $this->assertFalse($dept->allow_past_attendance);

        // Enable past attendance
        $this->actingAs($hod)
            ->post(route('departments.toggle-past-attendance'), [
                'department_id' => $dept->id,
                'allow_past_attendance' => 1,
            ])
            ->assertRedirect();

        $this->assertTrue($dept->refresh()->allow_past_attendance);

        // Disable past attendance
        $this->actingAs($hod)
            ->post(route('departments.toggle-past-attendance'), [
                'department_id' => $dept->id,
                'allow_past_attendance' => 0,
            ])
            ->assertRedirect();

        $this->assertFalse($dept->refresh()->allow_past_attendance);
    }

    public function test_faculty_past_attendance_marking_restrictions(): void
    {
        $session = LectureSession::query()
            ->with(['subjectAssignment.faculty.user', 'subjectAssignment.classSection.students'])
            ->firstOrFail();

        $faculty = $session->subjectAssignment->faculty->user;
        $dept = $session->subjectAssignment->classSection->program->department;

        // Make it a past session (e.g., yesterday)
        $session->update([
            'lecture_date' => today()->subDay(),
            'status' => 'scheduled',
            'submitted_at' => null,
        ]);

        // 1. By default (disabled), faculty should NOT be allowed to mark past attendance
        $dept->update(['allow_past_attendance' => false]);

        $this->actingAs($faculty)
            ->get(route('attendance.show', $session))
            ->assertOk(); // The page loads, but canMarkAttendance is false

        $student = $session->subjectAssignment->classSection->students->first();

        // Trying to save should fail
        $response = $this->actingAs($faculty)
            ->post(route('attendance.store', $session), [
                'attendance' => [$student->id => 'present'],
            ]);

        $response->assertSessionHasErrors('attendance');
        $this->assertNull($session->refresh()->submitted_at);

        // 2. If enabled, faculty should be allowed to mark past attendance within 7 days
        $dept->update(['allow_past_attendance' => true]);

        $this->actingAs($faculty)
            ->post(route('attendance.store', $session), [
                'attendance' => [$student->id => 'present'],
            ])
            ->assertRedirect();

        $this->assertNotNull($session->refresh()->submitted_at);
        $this->assertEquals('conducted', $session->status);

        // 3. If enabled, faculty should NOT be allowed to mark past attendance if older than 10 days (e.g., 11 days ago)
        $session->update([
            'lecture_date' => today()->subDays(11),
            'status' => 'scheduled',
            'submitted_at' => null,
        ]);

        $response = $this->actingAs($faculty)
            ->post(route('attendance.store', $session), [
                'attendance' => [$student->id => 'present'],
            ]);

        $response->assertSessionHasErrors('attendance');
        $this->assertNull($session->refresh()->submitted_at);
    }
}
