<?php

namespace Tests\Feature;

use App\Models\InternalMark;
use App\Models\InternalMarkComponent;
use App\Models\SubjectAssignment;
use App\Models\Student;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TranscriptAndBulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_faculty_can_download_import_template_and_bulk_import_marks(): void
    {
        $this->withoutVite();

        $assignment = SubjectAssignment::query()
            ->with(['faculty.user', 'classSection.students'])
            ->where('status', 'active')
            ->firstOrFail();

        $student = $assignment->classSection->students->firstOrFail();
        $faculty = $assignment->faculty->user;

        // Create internal marks
        InternalMark::create([
            'subject_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'mid_sem_30' => 30,
            'mid_sem_20' => 20,
            'cie_30' => 25,
            'total_50' => 45,
            'status' => 'submitted_to_exam',
            'submitted_at' => now(),
        ]);

        // Mark as released
        $assignment->update(['external_marks_status' => 'released']);

        // 1. Download template
        $response = $this->actingAs($faculty)
            ->get(route('marks.import-external-template', $assignment))
            ->assertOk();

        $csvContent = $response->streamedContent();
        $this->assertStringContainsString('"Roll No","Enrollment No","Student Name","External Mark (50)"', $csvContent);

        // 2. Upload template back with external marks
        $csvFileContent = "Roll No,Enrollment No,Student Name,External Mark (50)\n" .
                          "{$student->roll_no},{$student->enrollment_no},{$student->user->name},45.50\n";

        $file = UploadedFile::fake()->createWithContent('external_marks.csv', $csvFileContent);

        $this->actingAs($faculty)
            ->post(route('marks.import-external', $assignment), [
                'csv_file' => $file
            ])
            ->assertRedirect(route('marks.show', $assignment));

        $markRecord = InternalMark::where('subject_assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $this->assertEquals(45.50, $markRecord->external_50);
        $this->assertEquals(90.50, $markRecord->total_100);
    }

    public function test_student_and_staff_can_view_semester_grade_card(): void
    {
        $this->withoutVite();

        $assignment = SubjectAssignment::query()
            ->with(['subject', 'faculty.user', 'classSection.students.user'])
            ->where('status', 'active')
            ->firstOrFail();

        $studentUser = $assignment->classSection->students->firstOrFail()->user;
        $studentUser->update(['must_change_password' => false]);
        $student = $studentUser->student;

        // Set subject credits
        $assignment->subject->update(['credits' => 3]);

        // Submit external marks
        $mark = InternalMark::create([
            'subject_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'mid_sem_30' => 30,
            'mid_sem_20' => 20,
            'cie_30' => 25,
            'total_50' => 45,
            'external_50' => 40,
            'total_100' => 95, // 95 marks = O grade (grade point 10)
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $assignment->update(['external_marks_status' => 'submitted']);

        // Release results
        $student->classSection->update(['results_released' => true]);

        // 1. Student views their own grade card
        $response = $this->actingAs($studentUser)
            ->get(route('marks.student.semester-report'))
            ->assertOk()
            ->assertSeeText($assignment->subject->subject_code)
            ->assertSeeText('SGPA')
            ->assertSeeText('10.00'); // 10.00 GP since SGPA = 3*10 / 3 = 10.00

        // 2. Exam HOD views student's grade card
        $examHodUser = User::where('username', 'exam_hod')->firstOrFail();
        $examHodUser->update(['must_change_password' => false]);

        $this->actingAs($examHodUser)
            ->get(route('marks.semester-report', $student))
            ->assertOk()
            ->assertSeeText($assignment->subject->subject_code)
            ->assertSeeText('10.00');

        // 3. Random student views another student's grade card (should be forbidden)
        $anotherStudentUser = User::where('role', 'student')
            ->where('id', '!=', $studentUser->id)
            ->firstOrFail();
        $anotherStudentUser->update(['must_change_password' => false]);

        $this->actingAs($anotherStudentUser)
            ->get(route('marks.semester-report', $student))
            ->assertStatus(403);
    }

    public function test_hod_can_create_and_update_subject_with_credits(): void
    {
        $this->withoutVite();

        $hod = User::where('role', 'hod')->firstOrFail();
        $hod->update(['must_change_password' => false]);

        $program = \App\Models\Program::firstOrFail();
        $semester = $program->semesters()->firstOrFail();

        // 1. Create subject with credits
        $response = $this->actingAs($hod)
            ->post(route('academics.subjects.store'), [
                'program_id' => $program->id,
                'semester_id' => $semester->id,
                'subject_code' => 'TEST101',
                'subject_name' => 'Testing Subject',
                'credits' => 5,
            ])
            ->assertRedirect(route('academics.subjects.index'));

        $this->assertDatabaseHas('subjects', [
            'subject_code' => 'TEST101',
            'credits' => 5,
        ]);

        $subject = Subject::where('subject_code', 'TEST101')->firstOrFail();

        // 2. Update subject credits
        $this->actingAs($hod)
            ->put(route('academics.subjects.update', $subject), [
                'program_id' => $program->id,
                'semester_id' => $semester->id,
                'subject_code' => 'TEST101',
                'subject_name' => 'Testing Subject Updated',
                'credits' => 6,
                'status' => 'active',
            ])
            ->assertRedirect(route('academics.subjects.index'));

        $this->assertSame(6, $subject->fresh()->credits);
    }
}
