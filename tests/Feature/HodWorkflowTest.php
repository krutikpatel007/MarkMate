<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\LectureSession;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HodWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_hod_can_open_monitoring_and_timetable_pages(): void
    {
        $this->withoutVite();

        $hod = User::where('username', 'hod')->firstOrFail();
        $pendingSessions = LectureSession::whereIn('status', ['scheduled', 'pending'])->count();

        $this->actingAs($hod)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Total Lectures Today')
            ->assertSeeText('Defaulters Below 75%')
            ->assertSee('href="'.route('attendance.monitor', ['status' => 'pending']).'"', false)
            ->assertSee('<div class="stat">'.$pendingSessions.'</div>', false);

        $this->actingAs($hod)
            ->get(route('attendance.monitor'))
            ->assertOk()
            ->assertSee('Attendance Monitor')
            ->assertSee('All Attendance Marked by Faculty');

        $session = LectureSession::with('subjectAssignment.subject')->firstOrFail();

        $this->actingAs($hod)
            ->get(route('attendance.monitor', [
                'class_section_id' => $session->subjectAssignment->class_section_id,
                'subject_id' => $session->subjectAssignment->subject_id,
            ]))
            ->assertOk()
            ->assertSee('Subject')
            ->assertSee($session->subjectAssignment->subject->subject_name);

        $this->actingAs($hod)
            ->get(route('assignments.index'))
            ->assertOk()
            ->assertSee('Faculty Assignments');

        $this->actingAs($hod)
            ->get(route('staff.index'))
            ->assertOk()
            ->assertSee('Staff Users');

        $this->actingAs($hod)
            ->get(route('staff.create'))
            ->assertOk()
            ->assertSee('New Staff User');

        $this->actingAs($hod)
            ->get(route('staff.edit', User::where('username', 'faculty')->firstOrFail()))
            ->assertOk()
            ->assertSee('Edit Staff User');

        $this->actingAs($hod)
            ->get(route('timetables.index'))
            ->assertOk()
            ->assertSee('Weekly Timetable');

        $this->actingAs($hod)
            ->get(route('timetables.faculty'))
            ->assertOk()
            ->assertSee('Faculty-wise Timetable');
    }

    public function test_faculty_can_be_assigned_multiple_subjects_for_same_class(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $faculty = Faculty::where('employee_code', 'SCSA-FAC-001')->firstOrFail();
        $assignment = SubjectAssignment::query()
            ->with('subject')
            ->where('faculty_id', $faculty->id)
            ->firstOrFail();
        $secondSubject = Subject::query()
            ->where('program_id', $assignment->subject->program_id)
            ->where('semester_id', $assignment->subject->semester_id)
            ->where('id', '!=', $assignment->subject_id)
            ->firstOrFail();

        $this->actingAs($hod)
            ->post(route('assignments.store'), [
                'faculty_id' => $faculty->id,
                'class_section_id' => $assignment->class_section_id,
                'academic_year' => $assignment->academic_year,
                'subject_ids' => [$secondSubject->id],
                'status' => 'active',
            ])
            ->assertRedirect(route('assignments.index'));

        $this->assertDatabaseHas('subject_assignments', [
            'faculty_id' => $faculty->id,
            'subject_id' => $secondSubject->id,
            'class_section_id' => $assignment->class_section_id,
            'academic_year' => $assignment->academic_year,
        ]);

        $this->assertGreaterThanOrEqual(
            2,
            SubjectAssignment::where('faculty_id', $faculty->id)
                ->where('class_section_id', $assignment->class_section_id)
                ->count()
        );
    }

    public function test_hod_can_deactivate_used_assignment_instead_of_deleting_it(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $assignment = SubjectAssignment::query()
            ->whereHas('timetables')
            ->firstOrFail();

        $this->actingAs($hod)
            ->delete(route('assignments.destroy', $assignment))
            ->assertRedirect(route('assignments.index'));

        $this->assertSame('inactive', $assignment->fresh()->status);
    }

    public function test_pending_attendance_monitor_filter_includes_scheduled_sessions(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $session = LectureSession::with('subjectAssignment.subject')
            ->whereIn('status', ['scheduled', 'pending'])
            ->firstOrFail();

        $this->actingAs($hod)
            ->get(route('attendance.monitor', [
                'status' => 'pending',
                'class_section_id' => $session->subjectAssignment->class_section_id,
                'subject_id' => '',
            ]))
            ->assertOk()
            ->assertSee($session->subjectAssignment->subject->subject_name);
    }

    public function test_admin_can_create_hod_user(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $departmentId = $admin->fresh()->facultyProfile?->department_id
            ?? \App\Models\Department::firstOrFail()->id;

        $this->actingAs($admin)
            ->post(route('staff.store'), [
                'name' => 'Dr. Kavya Rao',
                'username' => 'new-hod',
                'email' => 'new-hod@scsa.local',
                'password' => 'temporary123',
                'password_confirmation' => 'temporary123',
                'role' => 'hod',
                'department_id' => $departmentId,
                'employee_code' => 'SCSA-HOD-002',
                'designation' => 'Head of Department',
                'display_initials' => 'KR',
            ])
            ->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'new-hod',
            'role' => 'hod',
            'must_change_password' => true,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('faculty', [
            'employee_code' => 'SCSA-HOD-002',
            'designation' => 'Head of Department',
            'status' => 'active',
        ]);
    }

    public function test_hod_can_create_faculty_user(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $departmentId = $hod->facultyProfile()->firstOrFail()->department_id;

        $this->actingAs($hod)
            ->post(route('staff.store'), [
                'name' => 'Prof. Nisha Vyas',
                'username' => 'nisha-vyas',
                'email' => 'nisha.vyas@scsa.local',
                'password' => 'temporary123',
                'password_confirmation' => 'temporary123',
                'role' => 'faculty',
                'department_id' => $departmentId,
                'employee_code' => 'SCSA-FAC-099',
                'designation' => 'Assistant Professor',
                'display_initials' => 'NV',
            ])
            ->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'nisha-vyas',
            'role' => 'faculty',
            'must_change_password' => true,
            'status' => 'active',
        ]);
    }

    public function test_hod_cannot_create_hod_user(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $departmentId = $hod->facultyProfile()->firstOrFail()->department_id;

        $this->actingAs($hod)
            ->post(route('staff.store'), [
                'name' => 'Dr. Unauthorized HOD',
                'username' => 'blocked-hod',
                'email' => 'blocked-hod@scsa.local',
                'password' => 'temporary123',
                'password_confirmation' => 'temporary123',
                'role' => 'hod',
                'department_id' => $departmentId,
                'employee_code' => 'SCSA-HOD-999',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', [
            'username' => 'blocked-hod',
        ]);
    }

    public function test_faculty_cannot_access_staff_management(): void
    {
        $faculty = User::where('username', 'faculty')->firstOrFail();

        $this->actingAs($faculty)
            ->get(route('staff.index'))
            ->assertForbidden();
    }

    public function test_hod_can_remove_faculty_from_active_use(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $facultyUser = User::where('username', 'faculty')->firstOrFail();
        $faculty = $facultyUser->facultyProfile()->firstOrFail();

        $this->actingAs($hod)
            ->patch(route('staff.status', $facultyUser), [
                'status' => 'inactive',
            ])
            ->assertRedirect(route('staff.index'));

        $this->assertSame('inactive', $facultyUser->fresh()->status);
        $this->assertSame('inactive', $faculty->fresh()->status);

        SubjectAssignment::where('faculty_id', $faculty->id)->each(function (SubjectAssignment $assignment) {
            $this->assertSame('inactive', $assignment->fresh()->status);
        });
    }
}
