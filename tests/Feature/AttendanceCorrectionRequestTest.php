<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectionRequest;
use App\Models\LectureSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_faculty_can_request_and_hod_can_approve_locked_attendance_correction(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $session = LectureSession::query()
            ->with(['attendanceRecords', 'subjectAssignment.faculty.user', 'subjectAssignment.classSection.students'])
            ->whereHas('attendanceRecords')
            ->firstOrFail();
        $faculty = $session->subjectAssignment->faculty->user;
        $session->update([
            'status' => 'locked',
            'submitted_at' => now()->subDays(2),
            'locked_at' => now(),
        ]);
        $record = $session->attendanceRecords->firstWhere('status', 'present') ?? $session->attendanceRecords->first();

        $this->actingAs($faculty)
            ->get(route('attendance.show', $session))
            ->assertOk()
            ->assertSeeText('Request Attendance Correction');

        // Create another HOD in a different department to verify they don't get spammed
        $otherDept = \App\Models\Department::create([
            'department_code' => 'OTHER',
            'department_name' => 'Other Department',
        ]);
        $otherHodUser = User::create([
            'name' => 'Other Hod',
            'username' => 'other_hod',
            'email' => 'other.hod@scsa.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'hod',
            'must_change_password' => false,
        ]);
        \App\Models\Faculty::create([
            'user_id' => $otherHodUser->id,
            'department_id' => $otherDept->id,
            'employee_code' => 'OTHER-HOD-001',
            'designation' => 'HOD Other',
        ]);

        $this->actingAs($faculty)
            ->post(route('attendance.correction-requests.store', $session), [
                'reason' => 'Student attendance was marked incorrectly.',
                'attendance' => [
                    $record->student_id => 'absent',
                ],
            ])
            ->assertRedirect(route('attendance.show', $session));

        $request = AttendanceCorrectionRequest::firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertSame('present', $request->requested_changes[$record->student_id]['from']);
        $this->assertSame('absent', $request->requested_changes[$record->student_id]['to']);

        // Assert notification was sent to target department HOD but not spammed to other HODs
        $this->assertDatabaseHas('notifications', [
            'user_id' => $hod->id,
            'title' => 'Attendance correction requested',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $otherHodUser->id,
            'title' => 'Attendance correction requested',
        ]);

        $this->actingAs($hod)
            ->patch(route('attendance-corrections.decide', $request), [
                'status' => 'approved',
                'decision_note' => 'Approved after verification.',
            ])
            ->assertRedirect(route('attendance-corrections.index'));

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame('absent', $record->fresh()->status);
    }

    public function test_faculty_cannot_request_correction_for_editable_session(): void
    {
        $session = LectureSession::query()
            ->with('subjectAssignment.faculty.user')
            ->whereHas('attendanceRecords')
            ->firstOrFail();
        $faculty = $session->subjectAssignment->faculty->user;
        $record = $session->attendanceRecords()->firstOrFail();

        $session->update([
            'status' => 'scheduled',
            'submitted_at' => null,
            'locked_at' => null,
        ]);

        $this->actingAs($faculty)
            ->post(route('attendance.correction-requests.store', $session), [
                'reason' => 'Trying too early.',
                'attendance' => [
                    $record->student_id => 'absent',
                ],
            ])
            ->assertSessionHasErrors('attendance');

        $this->assertDatabaseCount('attendance_correction_requests', 0);
    }

    public function test_hod_and_admin_cannot_directly_mark_or_save_attendance(): void
    {
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $admin = User::where('role', 'admin')->firstOrFail();

        $session = LectureSession::query()
            ->with('subjectAssignment.classSection.students')
            ->where('status', 'scheduled')
            ->firstOrFail();

        // 1. HOD should not be able to mark/save attendance (forbidden 403 on store, and canMarkAttendance is false)
        $this->actingAs($hod)
            ->get(route('attendance.show', $session))
            ->assertOk()
            ->assertDontSeeText('⚡ Quick-Mark All Present')
            ->assertSeeText('This attendance session is read-only.');

        $student = $session->subjectAssignment->classSection->students->firstOrFail();

        $this->actingAs($hod)
            ->post(route('attendance.store', $session), [
                'attendance' => [
                    $student->id => 'present',
                ],
            ])
            ->assertStatus(403);

        // 2. Admin should not be able to mark/save attendance (forbidden 403 on store, and canMarkAttendance is false)
        $this->actingAs($admin)
            ->get(route('attendance.show', $session))
            ->assertOk()
            ->assertDontSeeText('⚡ Quick-Mark All Present')
            ->assertSeeText('This attendance session is read-only.');

        $this->actingAs($admin)
            ->post(route('attendance.store', $session), [
                'attendance' => [
                    $student->id => 'present',
                ],
            ])
            ->assertStatus(403);
    }
}
