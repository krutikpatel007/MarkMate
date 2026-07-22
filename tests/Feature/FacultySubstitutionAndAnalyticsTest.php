<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LectureSession;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\SubjectAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultySubstitutionAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_hod_can_assign_substitute_faculty(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $session = LectureSession::query()->firstOrFail();
        
        // Find another active faculty member (different from the session's primary faculty)
        $primaryFacultyId = $session->subjectAssignment->faculty_id;
        $substituteFaculty = Faculty::where('id', '!=', $primaryFacultyId)
            ->where('status', 'active')
            ->whereHas('user', fn($q) => $q->where('role', 'faculty'))
            ->whereHas('department', fn($q) => $q->whereNotIn('department_code', ['EXAM', 'ADMIN', 'HR']))
            ->firstOrFail();

        $response = $this->actingAs($hod)
            ->post(route('attendance.monitor.substitute', $session), [
                'substitute_faculty_id' => $substituteFaculty->id,
            ]);

        $response->assertRedirect();
        $this->assertEquals($substituteFaculty->id, $session->refresh()->substitute_faculty_id);
    }

    public function test_substitute_faculty_can_view_and_mark_attendance(): void
    {
        $session = LectureSession::query()
            ->with(['subjectAssignment.faculty.user', 'subjectAssignment.classSection.students'])
            ->firstOrFail();

        $primaryFacultyId = $session->subjectAssignment->faculty_id;
        $substituteFaculty = Faculty::where('id', '!=', $primaryFacultyId)
            ->where('status', 'active')
            ->whereHas('user', fn($q) => $q->where('role', 'faculty'))
            ->whereHas('department', fn($q) => $q->whereNotIn('department_code', ['EXAM', 'ADMIN', 'HR']))
            ->firstOrFail();
        $substituteUser = $substituteFaculty->user;

        // Assign substitute
        $session->update([
            'substitute_faculty_id' => $substituteFaculty->id,
            'status' => 'scheduled',
            'submitted_at' => null,
        ]);

        // Substitute should be allowed to view the marking page
        $this->actingAs($substituteUser)
            ->get(route('attendance.show', $session))
            ->assertOk();

        // Substitute should be allowed to submit attendance
        $student = $session->subjectAssignment->classSection->students->firstOrFail();
        
        $response = $this->actingAs($substituteUser)
            ->post(route('attendance.store', $session), [
                'attendance' => [$student->id => 'present'],
            ]);

        $response->assertRedirect();
        $this->assertNotNull($session->refresh()->submitted_at);
        $this->assertEquals('conducted', $session->status);
    }

    public function test_substitute_faculty_sees_session_on_dashboard(): void
    {
        $session = LectureSession::query()->firstOrFail();
        $primaryFacultyId = $session->subjectAssignment->faculty_id;
        $substituteFaculty = Faculty::where('id', '!=', $primaryFacultyId)
            ->where('status', 'active')
            ->whereHas('user', fn($q) => $q->where('role', 'faculty'))
            ->whereHas('department', fn($q) => $q->whereNotIn('department_code', ['EXAM', 'ADMIN', 'HR']))
            ->firstOrFail();
        $substituteUser = $substituteFaculty->user;

        // Set session date to today and assign substitute
        $session->update([
            'substitute_faculty_id' => $substituteFaculty->id,
            'lecture_date' => today(),
        ]);

        $response = $this->actingAs($substituteUser)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('todaySessions', function ($sessions) use ($session) {
            return $sessions->contains('id', $session->id);
        });
    }

    public function test_visual_analytics_data_passed_to_heatmap_view(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();

        $response = $this->actingAs($hod)
            ->get(route('attendance.heatmap'));

        $response->assertOk();
        $response->assertViewHasAll([
            'heatmapData',
            'weekdayLabels',
            'weekdayPercentages',
            'lectureLabels',
            'lecturePercentages',
        ]);
    }
}
