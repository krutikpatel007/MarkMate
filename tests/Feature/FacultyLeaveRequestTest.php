<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\FacultyLeaveRequest;
use App\Models\LectureSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FacultyLeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_faculty_can_apply_for_leave_with_optional_attachment(): void
    {
        Storage::fake('public');
        $this->withoutVite();

        $facultyUser = User::where('role', 'faculty')->firstOrFail();
        $facultyUser->update(['must_change_password' => false]);
        $faculty = $facultyUser->facultyProfile;

        $file = UploadedFile::fake()->create('medical.pdf', 100);

        $response = $this->actingAs($facultyUser)
            ->post(route('leaves.faculty.store'), [
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-05',
                'reason' => 'Personal work.',
                'attachment' => $file,
            ]);

        $response->assertRedirect(route('leaves.faculty.index'));
        $leave = FacultyLeaveRequest::firstOrFail();
        $this->assertSame($faculty->id, $leave->faculty_id);
        $this->assertSame('2026-06-01', $leave->start_date->toDateString());
        $this->assertSame('2026-06-05', $leave->end_date->toDateString());
        $this->assertSame('Personal work.', $leave->reason);
        $this->assertSame('pending', $leave->status);

        $this->assertNotNull($leave->attachment_path);
        Storage::disk('public')->assertExists($leave->attachment_path);

        // Check in-app notification was created for HOD
        $hod = User::where('role', 'hod')->firstOrFail();
        $this->assertDatabaseHas('notifications', [
            'user_id' => $hod->id,
            'title' => 'New Faculty Leave Request',
        ]);
    }

    public function test_hod_can_approve_faculty_leave_and_cancel_scheduled_sessions_only(): void
    {
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $facultyUser = User::where('role', 'faculty')->firstOrFail();
        $facultyUser->update(['must_change_password' => false]);
        $faculty = $facultyUser->facultyProfile;

        // Find a lecture session for the faculty
        $scheduledSession = LectureSession::create([
            'subject_assignment_id' => $faculty->subjectAssignments()->firstOrFail()->id,
            'lecture_date' => '2026-06-01',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'scheduled',
        ]);

        $conductedSession = LectureSession::create([
            'subject_assignment_id' => $faculty->subjectAssignments()->firstOrFail()->id,
            'lecture_date' => '2026-06-01',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'conducted',
        ]);

        $leave = FacultyLeaveRequest::create([
            'faculty_id' => $faculty->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'reason' => 'Family emergency',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($hod)
            ->patch(route('leaves.faculty.hod.decide', $leave), [
                'status' => 'approved',
                'decision_note' => 'Approved, take care.',
            ]);

        $response->assertRedirect(route('leaves.faculty.hod.index'));
        $this->assertSame('approved', $leave->fresh()->status);

        // Verify scheduled session was cancelled
        $this->assertSame('cancelled', $scheduledSession->fresh()->status);

        // Verify conducted session was NOT cancelled
        $this->assertSame('conducted', $conductedSession->fresh()->status);

        // Verify in-app notification to faculty
        $this->assertDatabaseHas('notifications', [
            'user_id' => $facultyUser->id,
            'title' => 'Leave Request Approved',
        ]);
    }

    public function test_faculty_leave_attachment_is_deleted_on_rejection(): void
    {
        Storage::fake('public');
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $facultyUser = User::where('role', 'faculty')->firstOrFail();
        $facultyUser->update(['must_change_password' => false]);
        $faculty = $facultyUser->facultyProfile;

        $file = UploadedFile::fake()->create('medical.pdf', 100);
        $path = Storage::disk('public')->putFile('faculty-leaves', $file);

        $leave = FacultyLeaveRequest::create([
            'faculty_id' => $faculty->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'reason' => 'Family emergency',
            'attachment_path' => $path,
            'status' => 'pending',
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->actingAs($hod)
            ->patch(route('leaves.faculty.hod.decide', $leave), [
                'status' => 'rejected',
                'decision_note' => 'Rejected.',
            ]);

        $response->assertRedirect(route('leaves.faculty.hod.index'));
        $this->assertSame('rejected', $leave->fresh()->status);
        $this->assertNull($leave->fresh()->attachment_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_faculty_leave_attachment_is_deleted_on_model_deletion(): void
    {
        Storage::fake('public');

        $facultyUser = User::where('role', 'faculty')->firstOrFail();
        $faculty = $facultyUser->facultyProfile;

        $file = UploadedFile::fake()->create('medical.pdf', 100);
        $path = Storage::disk('public')->putFile('faculty-leaves', $file);

        $leave = FacultyLeaveRequest::create([
            'faculty_id' => $faculty->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'reason' => 'Family emergency',
            'attachment_path' => $path,
            'status' => 'pending',
        ]);

        Storage::disk('public')->assertExists($path);

        $leave->delete();

        Storage::disk('public')->assertMissing($path);
    }
}
