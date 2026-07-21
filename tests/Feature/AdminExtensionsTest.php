<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Semester;
use App\Models\ClassSection;
use App\Models\Program;
use App\Models\Department;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExtensionsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $admin;
    private User $facultyUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', 'admin')->firstOrFail();
        $this->facultyUser = User::where('role', 'faculty')->firstOrFail();
    }

    public function test_non_admins_are_forbidden_from_admin_backups(): void
    {
        $response = $this->actingAs($this->facultyUser)->get(route('admin.backups.index'));
        $response->assertStatus(403);
    }

    public function test_admins_can_access_backups_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.backups.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.backups.index');
    }

    public function test_non_admins_are_forbidden_from_audit_logs(): void
    {
        $response = $this->actingAs($this->facultyUser)->get(route('admin.audit-logs.index'));
        $response->assertStatus(403);
    }

    public function test_admins_can_access_audit_logs_explorer(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.audit-logs.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.audit_logs.index');
    }

    public function test_students_promotion_logic_works_as_expected(): void
    {
        // Setup academic tables
        $dept = Department::create(['department_name' => 'TESTDEPT', 'department_code' => 'TESTDEPT']);
        
        $program = Program::create([
            'department_id' => $dept->id,
            'program_name' => 'Test Program',
            'program_code' => 'TESTPROG',
        ]);

        $sem3 = Semester::create([
            'program_id' => $program->id,
            'semester_no' => 3,
            'semester_name' => '3rd Semester',
        ]);

        $sem4 = Semester::create([
            'program_id' => $program->id,
            'semester_no' => 4,
            'semester_name' => '4th Semester',
        ]);

        $sourceClass = ClassSection::create([
            'program_id' => $program->id,
            'semester_id' => $sem3->id,
            'section_name' => 'A',
            'display_name' => '3TEST - A',
        ]);

        $targetClass = ClassSection::create([
            'program_id' => $program->id,
            'semester_id' => $sem4->id,
            'section_name' => 'A',
            'display_name' => '4TEST - A',
        ]);

        // Create student
        $studentUser = User::create([
            'name' => 'Test Student',
            'username' => 'teststudent',
            'email' => 'student@shreyarth.edu.in',
            'password' => bcrypt('password'),
            'role' => 'student',
            'password_changed_at' => now(),
        ]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'program_id' => $program->id,
            'semester_id' => $sem3->id,
            'class_section_id' => $sourceClass->id,
            'enrollment_no' => 'EN12345',
            'roll_no' => '101',
            'status' => 'active',
        ]);

        // Act: Promote students
        $response = $this->actingAs($this->admin)->post(route('admin.promotion.promote'), [
            'source_class_section_id' => $sourceClass->id,
            'action_type' => 'promote',
            'target_class_section_id' => $targetClass->id,
        ]);

        $response->assertRedirect(route('admin.promotion.index'));
        $response->assertSessionHas('success');

        // Assert: Student record is updated
        $student->refresh();
        $this->assertEquals($targetClass->id, $student->class_section_id);
        $this->assertEquals($sem4->id, $student->semester_id);

        // Assert: Audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'students_promoted',
            'entity_type' => ClassSection::class,
            'entity_id' => $sourceClass->id,
        ]);
    }
}
