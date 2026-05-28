<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassAttendanceExportTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_hod_can_export_classwise_attendance_csv(): void
    {
        $this->withoutVite();

        $hod = User::where('username', 'hod')->firstOrFail();
        $assignment = SubjectAssignment::with(['classSection', 'subject'])
            ->whereHas('lectureSessions.attendanceRecords')
            ->firstOrFail();
        $section = $assignment->classSection;

        $this->actingAs($hod)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSeeText('Export CSV');

        $response = $this->actingAs($hod)
            ->get(route('reports.class-attendance.export', [
                'class_section_id' => $section->id,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('class,enrollment_no,roll_no,student_name,subject_code,subject_name,conducted_lectures,present,absent,absent_with_leave,attendance_percentage', $csv);
        $this->assertStringContainsString('SU2026BCA001', $csv);
        $this->assertStringContainsString('Riya Patel', $csv);
        $this->assertStringContainsString($assignment->subject->subject_code, $csv);
    }

    public function test_faculty_cannot_export_classwise_attendance_csv(): void
    {
        $faculty = User::where('username', 'faculty')->firstOrFail();
        $section = ClassSection::firstOrFail();

        $this->actingAs($faculty)
            ->get(route('reports.class-attendance.export', [
                'class_section_id' => $section->id,
            ]))
            ->assertForbidden();
    }

    public function test_hod_can_export_subjectwise_detailed_attendance_csv(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $assignment = SubjectAssignment::query()
            ->with(['subject', 'classSection'])
            ->whereHas('lectureSessions.attendanceRecords')
            ->firstOrFail();

        $response = $this->actingAs($hod)
            ->get(route('reports.subject-attendance.export', [
                'subject_assignment_id' => $assignment->id,
                'academic_term' => 'Odd 2025',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('SHREYARTH UNIVERSITY', $csv);
        $this->assertStringContainsString('Attendance Sheet', $csv);
        $this->assertStringContainsString($assignment->subject->subject_code.' - '.$assignment->subject->subject_name, $csv);
        $this->assertStringContainsString('Academic Term: Odd 2025', $csv);
        $this->assertStringContainsString('"Sr. No","Enrollment No","Name of Student",DAY', $csv);
        $this->assertStringContainsString('SU2026BCA001', $csv);
        $this->assertStringContainsString('Riya Patel', $csv);
    }
}
