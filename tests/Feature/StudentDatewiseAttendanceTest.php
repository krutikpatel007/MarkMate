<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDatewiseAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_student_can_view_datewise_attendance_on_profile(): void
    {
        $this->withoutVite();

        $record = AttendanceRecord::query()
            ->with(['student.user', 'lectureSession.subjectAssignment.subject', 'lectureSession.subjectAssignment.faculty.user'])
            ->firstOrFail();
        $studentUser = $record->student->user;
        $studentUser->update(['must_change_password' => false]);
        $session = $record->lectureSession;

        $this->actingAs($studentUser)
            ->get(route('dashboard', [
                'attendance_date' => $session->lecture_date->toDateString(),
            ]))
            ->assertOk()
            ->assertSeeText('Datewise Attendance')
            ->assertSeeText($session->subjectAssignment->subject->subject_code)
            ->assertSeeText($session->subjectAssignment->subject->subject_name)
            ->assertSeeText($session->subjectAssignment->faculty->user->name)
            ->assertSeeText(ucwords(str_replace('_', ' ', $record->status)));
    }

    public function test_student_datewise_attendance_shows_empty_state(): void
    {
        $studentUser = User::where('role', 'student')->firstOrFail();
        $studentUser->update(['must_change_password' => false]);

        $this->actingAs($studentUser)
            ->get(route('dashboard', ['attendance_date' => '2030-01-01']))
            ->assertOk()
            ->assertSeeText('Datewise Attendance')
            ->assertSeeText('No data found.!');
    }
}
