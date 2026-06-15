<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentLeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_student_can_apply_for_leave_with_optional_attachment(): void
    {
        Storage::fake('public');
        $this->withoutVite();

        $studentUser = User::where('role', 'student')->firstOrFail();
        $studentUser->update(['must_change_password' => false]);
        $student = $studentUser->student;

        $file = UploadedFile::fake()->create('medical.pdf', 100);

        $response = $this->actingAs($studentUser)
            ->post(route('leaves.student.store'), [
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-05',
                'reason' => 'Severe viral fever, advised bed rest.',
                'attachment' => $file,
            ]);

        $response->assertRedirect(route('leaves.student.index'));
        $leave = LeaveRequest::firstOrFail();
        $this->assertSame($student->id, $leave->student_id);
        $this->assertSame('2026-06-01', $leave->start_date->toDateString());
        $this->assertSame('2026-06-05', $leave->end_date->toDateString());
        $this->assertSame('Severe viral fever, advised bed rest.', $leave->reason);
        $this->assertSame('pending', $leave->status);

        $leave = LeaveRequest::firstOrFail();
        $this->assertNotNull($leave->attachment_path);
        Storage::disk('public')->assertExists($leave->attachment_path);

        // Check in-app notification was created for HOD
        $hod = User::where('role', 'hod')->firstOrFail();
        $this->assertDatabaseHas('notifications', [
            'user_id' => $hod->id,
            'title' => 'New Leave Request',
        ]);
    }

    public function test_student_can_view_their_leaves_list(): void
    {
        $this->withoutVite();

        $studentUser = User::where('role', 'student')->firstOrFail();
        $studentUser->update(['must_change_password' => false]);
        $student = $studentUser->student;

        LeaveRequest::create([
            'student_id' => $student->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-03',
            'reason' => 'Family function',
            'status' => 'pending',
        ]);

        $this->actingAs($studentUser)
            ->get(route('leaves.student.index'))
            ->assertOk()
            ->assertSeeText('Family function')
            ->assertSeeText('pending');
    }

    public function test_hod_can_approve_leave_request_and_override_past_attendance(): void
    {
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $studentUser = User::where('role', 'student')->firstOrFail();
        $studentUser->update(['must_change_password' => false]);
        $student = $studentUser->student;

        // Find or create an attendance record for the student to verify override
        $record = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->firstOrFail();
        
        $session = $record->lectureSession;
        $session->update([
            'lecture_date' => '2026-06-01',
            'status' => 'conducted',
        ]);
        $record->update(['status' => 'absent']);

        $leave = LeaveRequest::create([
            'student_id' => $student->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'reason' => 'Medical checkup',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($hod)
            ->patch(route('leaves.hod.decide', $leave), [
                'status' => 'approved',
                'decision_note' => 'Approved, get well soon.',
            ]);

        $response->assertRedirect(route('leaves.hod.index'));
        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame('Approved, get well soon.', $leave->fresh()->decision_note);

        // Verify attendance record was overridden to absent_with_leave
        $this->assertSame('absent_with_leave', $record->fresh()->status);

        // Verify in-app notification to student
        $this->assertDatabaseHas('notifications', [
            'user_id' => $studentUser->id,
            'title' => 'Leave Request Approved',
        ]);
    }

    public function test_hod_can_reject_leave_request(): void
    {
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $studentUser = User::where('role', 'student')->firstOrFail();
        $studentUser->update(['must_change_password' => false]);
        $student = $studentUser->student;

        $leave = LeaveRequest::create([
            'student_id' => $student->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-02',
            'reason' => 'Going home',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($hod)
            ->patch(route('leaves.hod.decide', $leave), [
                'status' => 'rejected',
                'decision_note' => 'Rejected due to low attendance context.',
            ]);

        $response->assertRedirect(route('leaves.hod.index'));
        $this->assertSame('rejected', $leave->fresh()->status);
        $this->assertSame('Rejected due to low attendance context.', $leave->fresh()->decision_note);

        // Verify in-app notification to student
        $this->assertDatabaseHas('notifications', [
            'user_id' => $studentUser->id,
            'title' => 'Leave Request Rejected',
        ]);
    }

    public function test_present_attendance_records_are_not_overwritten_on_leave_approval(): void
    {
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $studentUser = User::where('role', 'student')->firstOrFail();
        $studentUser->update(['must_change_password' => false]);
        $student = $studentUser->student;

        // Find or create an attendance record for the student to verify override
        $record = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->firstOrFail();
        
        $session = $record->lectureSession;
        $session->update([
            'lecture_date' => '2026-06-01',
            'status' => 'conducted',
        ]);
        $record->update(['status' => 'present']);

        $leave = LeaveRequest::create([
            'student_id' => $student->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'reason' => 'Medical checkup',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($hod)
            ->patch(route('leaves.hod.decide', $leave), [
                'status' => 'approved',
                'decision_note' => 'Approved, get well soon.',
            ]);

        $response->assertRedirect(route('leaves.hod.index'));
        $this->assertSame('approved', $leave->fresh()->status);

        // Verify attendance record was NOT overwritten to absent_with_leave since it was present
        $this->assertSame('present', $record->fresh()->status);
    }

    public function test_student_leave_attachment_is_deleted_on_rejection(): void
    {
        Storage::fake('public');
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $studentUser = User::where('role', 'student')->firstOrFail();
        $studentUser->update(['must_change_password' => false]);
        $student = $studentUser->student;

        $file = UploadedFile::fake()->create('medical.pdf', 100);
        $path = Storage::disk('public')->putFile('leaves', $file);

        $leave = LeaveRequest::create([
            'student_id' => $student->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'reason' => 'Medical checkup',
            'attachment_path' => $path,
            'status' => 'pending',
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->actingAs($hod)
            ->patch(route('leaves.hod.decide', $leave), [
                'status' => 'rejected',
                'decision_note' => 'Rejected.',
            ]);

        $response->assertRedirect(route('leaves.hod.index'));
        $this->assertSame('rejected', $leave->fresh()->status);
        $this->assertNull($leave->fresh()->attachment_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_student_leave_attachment_is_deleted_on_model_deletion(): void
    {
        Storage::fake('public');

        $studentUser = User::where('role', 'student')->firstOrFail();
        $student = $studentUser->student;

        $file = UploadedFile::fake()->create('medical.pdf', 100);
        $path = Storage::disk('public')->putFile('leaves', $file);

        $leave = LeaveRequest::create([
            'student_id' => $student->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'reason' => 'Medical checkup',
            'attachment_path' => $path,
            'status' => 'pending',
        ]);

        Storage::disk('public')->assertExists($path);

        $leave->delete();

        Storage::disk('public')->assertMissing($path);
    }
}
