<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use App\Models\Student;
use App\Models\Faculty;
use App\Models\ClassSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * 1. ADMIN ROLE ACCESS TESTS
     */
    public function test_admin_has_global_access_to_all_management_endpoints(): void
    {
        $this->withoutVite();

        $admin = User::where('role', 'admin')->firstOrFail();

        // Admin can manage departments
        $this->actingAs($admin)
            ->get(route('departments.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('departments.create'))
            ->assertOk();

        // Admin can manage programs
        $this->actingAs($admin)
            ->get(route('programs.index'))
            ->assertOk();

        // Admin can manage staff
        $this->actingAs($admin)
            ->get(route('staff.index'))
            ->assertOk();
    }

    /**
     * 2. HOD ROLE ACCESS TESTS
     */
    public function test_hod_is_restricted_from_global_departments_but_can_manage_departmental_data(): void
    {
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);

        // HOD cannot manage departments
        $this->actingAs($hod)
            ->get(route('departments.index'))
            ->assertForbidden();

        $this->actingAs($hod)
            ->get(route('departments.create'))
            ->assertForbidden();

        // HOD can manage programs (within department)
        $this->actingAs($hod)
            ->get(route('programs.index'))
            ->assertOk();

        // HOD can manage staff (within department)
        $this->actingAs($hod)
            ->get(route('staff.index'))
            ->assertOk();
    }

    /**
     * 3. FACULTY ROLE ACCESS TESTS
     */
    public function test_faculty_is_forbidden_from_all_administrative_management_endpoints(): void
    {
        $this->withoutVite();

        $faculty = User::where('role', 'faculty')->firstOrFail();
        $faculty->update(['must_change_password' => false]);

        // Faculty cannot manage departments
        $this->actingAs($faculty)
            ->get(route('departments.index'))
            ->assertForbidden();

        // Faculty cannot manage programs
        $this->actingAs($faculty)
            ->get(route('programs.index'))
            ->assertForbidden();

        // Faculty cannot manage academic setups
        $this->actingAs($faculty)
            ->get(route('setup.index'))
            ->assertForbidden();

        // Faculty cannot manage staff users
        $this->actingAs($faculty)
            ->get(route('staff.index'))
            ->assertForbidden();

        // Faculty cannot monitor attendance globally
        $this->actingAs($faculty)
            ->get(route('attendance.monitor'))
            ->assertForbidden();
    }

    /**
     * 4. STUDENT ROLE ACCESS TESTS
     */
    public function test_student_is_blocked_from_all_faculty_and_staff_management_endpoints(): void
    {
        $this->withoutVite();

        $studentUser = User::where('role', 'student')->firstOrFail();
        $studentUser->update(['must_change_password' => false]);

        // Student cannot manage departments
        $this->actingAs($studentUser)
            ->get(route('departments.index'))
            ->assertForbidden();

        // Student cannot manage programs
        $this->actingAs($studentUser)
            ->get(route('programs.index'))
            ->assertForbidden();

        // Student cannot manage timetables
        $this->actingAs($studentUser)
            ->get(route('timetables.index'))
            ->assertForbidden();

        // Student cannot view faculty assignments
        $this->actingAs($studentUser)
            ->get(route('assignments.index'))
            ->assertForbidden();

        // Student cannot access global internal marks management
        $this->actingAs($studentUser)
            ->get(route('marks.index'))
            ->assertForbidden();

        // Student can view their own scorecard
        $this->actingAs($studentUser)
            ->get(route('marks.student'))
            ->assertOk();
    }

    /**
     * 5. EXHAUSTIVE STAFF FIELD VALIDATION TESTS
     */
    public function test_staff_name_validation(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        // Name is required
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['name' => '']))
            ->assertSessionHasErrors('name');

        // Name cannot exceed 255 characters
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['name' => str_repeat('a', 256)]))
            ->assertSessionHasErrors('name');
    }

    public function test_staff_username_validation(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $existingUser = User::firstOrFail();

        // Username is required
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['username' => '']))
            ->assertSessionHasErrors('username');

        // Username must be unique
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['username' => $existingUser->username]))
            ->assertSessionHasErrors('username');

        // Username cannot exceed 255 characters
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['username' => str_repeat('a', 256)]))
            ->assertSessionHasErrors('username');
    }

    public function test_staff_email_validation(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $existingUser = User::whereNotNull('email')->firstOrFail();

        // Email must be a valid email format
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');

        // Email must be unique
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['email' => $existingUser->email]))
            ->assertSessionHasErrors('email');

        // Email cannot exceed 255 characters
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['email' => str_repeat('a', 244) . '@example.com']))
            ->assertSessionHasErrors('email');
    }

    public function test_staff_password_validation(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        // Password is required on create
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['password' => '', 'password_confirmation' => '']))
            ->assertSessionHasErrors('password');

        // Password must be at least 8 characters
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['password' => '1234567', 'password_confirmation' => '1234567']))
            ->assertSessionHasErrors('password');

        // Password confirmation must match
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['password' => 'password123', 'password_confirmation' => 'mismatch123']))
            ->assertSessionHasErrors('password');
    }

    public function test_staff_role_validation(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        // Role is required
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['role' => '']))
            ->assertSessionHasErrors('role');

        // Role must be manageable
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['role' => 'invalid_role']))
            ->assertSessionHasErrors('role');
    }

    public function test_staff_department_id_validation(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        // Department ID is required
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['department_id' => '']))
            ->assertSessionHasErrors('department_id');

        // Department ID must exist
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['department_id' => 99999]))
            ->assertSessionHasErrors('department_id');
    }

    public function test_staff_employee_code_validation(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $existingFaculty = Faculty::firstOrFail();

        // Employee code is required
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['employee_code' => '']))
            ->assertSessionHasErrors('employee_code');

        // Employee code must be unique
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['employee_code' => $existingFaculty->employee_code]))
            ->assertSessionHasErrors('employee_code');

        // Employee code cannot exceed 255 characters
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['employee_code' => str_repeat('a', 256)]))
            ->assertSessionHasErrors('employee_code');
    }

    public function test_staff_designation_validation(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        // Designation cannot exceed 255 characters
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['designation' => str_repeat('a', 256)]))
            ->assertSessionHasErrors('designation');
    }

    public function test_staff_display_initials_validation(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        // Display initials cannot exceed 12 characters
        $this->actingAs($admin)
            ->post(route('staff.store'), $this->validStaffPayload(['display_initials' => str_repeat('a', 13)]))
            ->assertSessionHasErrors('display_initials');
    }

    public function test_staff_status_validation(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $staffUser = User::where('role', 'faculty')->firstOrFail();

        // Status must be valid (active/inactive) on update
        $this->actingAs($admin)
            ->put(route('staff.update', $staffUser), $this->validStaffPayload(['status' => 'invalid_status']))
            ->assertSessionHasErrors('status');
    }

    /**
     * 6. EXHAUSTIVE STUDENT FIELD VALIDATION TESTS
     */
    public function test_student_name_validation(): void
    {
        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);

        // Name is required
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['name' => '']))
            ->assertSessionHasErrors('name');

        // Name cannot exceed 255 characters
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['name' => str_repeat('a', 256)]))
            ->assertSessionHasErrors('name');
    }

    public function test_student_username_validation(): void
    {
        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);
        $existingUser = User::firstOrFail();

        // Username is required
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['username' => '']))
            ->assertSessionHasErrors('username');

        // Username must be unique
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['username' => $existingUser->username]))
            ->assertSessionHasErrors('username');

        // Username cannot exceed 255 characters
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['username' => str_repeat('a', 256)]))
            ->assertSessionHasErrors('username');
    }

    public function test_student_email_validation(): void
    {
        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);
        $existingUser = User::whereNotNull('email')->firstOrFail();

        // Email must be unique
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['email' => $existingUser->email]))
            ->assertSessionHasErrors('email');

        // Email must be a valid email format
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['email' => 'invalid-email']))
            ->assertSessionHasErrors('email');

        // Email cannot exceed 255 characters
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['email' => str_repeat('a', 244) . '@example.com']))
            ->assertSessionHasErrors('email');
    }

    public function test_student_enrollment_no_validation(): void
    {
        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);
        $existingStudent = Student::firstOrFail();

        // Enrollment number is required
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['enrollment_no' => '']))
            ->assertSessionHasErrors('enrollment_no');

        // Enrollment number must be unique
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['enrollment_no' => $existingStudent->enrollment_no]))
            ->assertSessionHasErrors('enrollment_no');

        // Enrollment number cannot exceed 64 characters
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['enrollment_no' => str_repeat('a', 65)]))
            ->assertSessionHasErrors('enrollment_no');
    }

    public function test_student_roll_no_validation(): void
    {
        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);

        // Roll number cannot exceed 32 characters
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['roll_no' => str_repeat('a', 33)]))
            ->assertSessionHasErrors('roll_no');
    }

    public function test_student_mobile_validation(): void
    {
        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);

        // Mobile cannot exceed 20 characters
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['mobile' => str_repeat('1', 21)]))
            ->assertSessionHasErrors('mobile');
    }

    public function test_student_class_section_id_validation(): void
    {
        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);

        // Class Section ID is required
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['class_section_id' => '']))
            ->assertSessionHasErrors('class_section_id');

        // Class Section ID must exist
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['class_section_id' => 99999]))
            ->assertSessionHasErrors('class_section_id');
    }

    public function test_student_password_validation(): void
    {
        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);

        // Password must be at least 8 characters
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['password' => '1234567', 'password_confirmation' => '1234567']))
            ->assertSessionHasErrors('password');

        // Password confirmation must match
        $this->actingAs($hod)
            ->post(route('academics.students.store'), $this->validStudentPayload(['password' => 'student123', 'password_confirmation' => 'mismatch123']))
            ->assertSessionHasErrors('password');
    }

    public function test_student_status_validation(): void
    {
        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);
        $student = Student::firstOrFail();

        // Status must be valid (active/inactive) on update
        $this->actingAs($hod)
            ->put(route('academics.students.update', $student), $this->validStudentPayload(['status' => 'invalid_status']))
            ->assertSessionHasErrors('status');
    }

    /**
     * HELPER METHODS
     */
    private function validStaffPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Prof. John Doe',
            'username' => 'john.doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'faculty',
            'department_id' => Department::firstOrFail()->id,
            'employee_code' => 'EMP-JD-999',
            'designation' => 'Assistant Professor',
            'display_initials' => 'JD',
            'status' => 'active',
        ], $overrides);
    }

    private function validStudentPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Smith',
            'username' => 'jane.smith',
            'email' => 'jane.smith@example.com',
            'enrollment_no' => 'SU2026BCA999',
            'roll_no' => '999',
            'mobile' => '9876543210',
            'class_section_id' => ClassSection::firstOrFail()->id,
            'password' => 'student123',
            'password_confirmation' => 'student123',
            'status' => 'active',
        ], $overrides);
    }

    public function test_admin_can_create_coe_and_admin_staff_users(): void
    {
        $this->withoutVite();
        $admin = User::where('role', 'admin')->firstOrFail();

        // Create COE
        $coePayload = $this->validStaffPayload([
            'username' => 'test.coe',
            'email' => 'coe@example.com',
            'role' => 'coe',
            'employee_code' => 'EMP-COE-100',
            'designation' => null // should default to Controller of Examinations
        ]);

        $this->actingAs($admin)
            ->post(route('staff.store'), $coePayload)
            ->assertRedirect(route('staff.index'));

        $coeUser = User::where('username', 'test.coe')->firstOrFail();
        $this->assertEquals('coe', $coeUser->role);
        $this->assertEquals('Controller of Examinations', $coeUser->facultyProfile->designation);

        // Create Admin Staff
        $staffPayload = $this->validStaffPayload([
            'username' => 'test.admin_staff',
            'email' => 'admin_staff@example.com',
            'role' => 'admin_staff',
            'employee_code' => 'EMP-AS-100',
            'designation' => null // should default to Admin Staff
        ]);

        $this->actingAs($admin)
            ->post(route('staff.store'), $staffPayload)
            ->assertRedirect(route('staff.index'));

        $asUser = User::where('username', 'test.admin_staff')->firstOrFail();
        $this->assertEquals('admin_staff', $asUser->role);
        $this->assertEquals('Admin Staff', $asUser->facultyProfile->designation);
    }

    public function test_coe_has_exam_dashboard_and_marksheet_access(): void
    {
        $this->withoutVite();
        
        $coe = User::factory()->create([
            'username' => 'coe_user_test',
            'role' => 'coe',
            'must_change_password' => false
        ]);
        Faculty::create([
            'user_id' => $coe->id,
            'department_id' => Department::firstOrFail()->id,
            'employee_code' => 'EMP-COE-101',
            'designation' => 'Controller of Examinations',
            'status' => 'active',
        ]);

        // COE can view Dashboard
        $this->actingAs($coe)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('isExamDept', true);

        // COE can view student master data
        $this->actingAs($coe)
            ->get(route('academics.students.index'))
            ->assertOk();

        // COE can view Marks Scrutiny
        $this->actingAs($coe)
            ->get(route('exam.scrutiny.index'))
            ->assertOk();
    }

    public function test_admin_staff_has_academic_management_and_leave_approval_access(): void
    {
        $this->withoutVite();

        $as = User::factory()->create([
            'username' => 'as_user_test',
            'role' => 'admin_staff',
            'must_change_password' => false
        ]);
        Faculty::create([
            'user_id' => $as->id,
            'department_id' => Department::firstOrFail()->id,
            'employee_code' => 'EMP-AS-101',
            'designation' => 'Admin Staff',
            'status' => 'active',
        ]);

        // Admin Staff can view student master data
        $this->actingAs($as)
            ->get(route('academics.students.index'))
            ->assertOk();

        // Admin Staff can view notices management
        $this->actingAs($as)
            ->get(route('notices.index'))
            ->assertOk();

        // Admin Staff can view student leave requests
        $this->actingAs($as)
            ->get(route('leaves.hod.index'))
            ->assertOk();
    }
}
