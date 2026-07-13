<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\ClassSection;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\InternalMark;
use App\Models\InternalMarkComponent;
use App\Models\InternalMarkValue;
use App\Models\LectureSession;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // SAFETY GUARD — Prevents accidental wipe of real data
        // ============================================================
        $studentCount = Student::count();
        if ($studentCount > 10) {
            $this->command->error('');
            $this->command->error('  ⚠  REAL DATA DETECTED — SampleDataSeeder Aborted!');
            $this->command->error("  ⚠  Found {$studentCount} students in the database.");
            $this->command->error('  ⚠  This seeder deletes ALL lecture sessions and attendance records.');
            $this->command->error('');
            $this->command->warn('  Backup first, then reset intentionally:');
            $this->command->warn('  php artisan db:backup --label=before-reset');
            $this->command->warn('  php artisan migrate:fresh --seed --force');
            $this->command->error('');
            return;
        }
        // ============================================================

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Fetch ClassSection 1 (MSC A), Program 2, Semester 18
        $classSection = ClassSection::find(1);
        if (!$classSection) {
            $this->command->error("ClassSection 1 not found!");
            return;
        }

        $program = $classSection->program;
        $semester = $classSection->semester;
        $department = $program->department;

        $this->command->info("Selected ClassSection: {$classSection->display_name}");

        // Deactivate all existing subjects to ensure we only calculate using our 4 new subjects
        Subject::query()->where('program_id', $program->id)->where('semester_id', $semester->id)->update(['status' => 'inactive']);

        // 2. Define the 4 faculty members and subjects
        $facultyData = [
            [
                'name' => 'Parth Patel',
                'username' => 'parth',
                'employee_code' => 'SCSA-FAC-PP',
                'display_initials' => 'PP',
                'subject_code' => 'AIML',
                'subject_name' => 'Artificial Intelligence & Machine Learning',
            ],
            [
                'name' => 'Jinal Bhatt',
                'username' => 'jinal',
                'employee_code' => 'SCSA-FAC-JB',
                'display_initials' => 'JB',
                'subject_code' => 'CIDP',
                'subject_name' => 'Continuous Integration and Delivery Practices',
            ],
            [
                'name' => 'Sanyam Sheth',
                'username' => 'sanyam',
                'employee_code' => 'SCSA-FAC-SS',
                'display_initials' => 'SS',
                'subject_code' => 'DAP',
                'subject_name' => 'Data Analytics Principles',
            ],
            [
                'name' => 'Ankita Chauhan',
                'username' => 'ankita',
                'employee_code' => 'SCSA-FAC-AC',
                'display_initials' => 'AC',
                'subject_code' => 'MAD',
                'subject_name' => 'Mobile Application Development',
            ],
        ];

        // Delete any existing entries for these subject codes to prevent duplicates
        $subjectCodes = collect($facultyData)->pluck('subject_code')->toArray();
        $existingSubjects = Subject::whereIn('subject_code', $subjectCodes)->get();
        foreach ($existingSubjects as $sub) {
            $assignments = SubjectAssignment::where('subject_id', $sub->id)->get();
            foreach ($assignments as $assign) {
                $marks = InternalMark::where('subject_assignment_id', $assign->id)->get();
                foreach ($marks as $m) {
                    InternalMarkValue::where('internal_mark_id', $m->id)->delete();
                    $m->delete();
                }
                InternalMarkComponent::where('subject_assignment_id', $assign->id)->delete();
                $assign->delete();
            }
            $sub->delete();
        }

        // 4. Create Faculty, Subjects, and SubjectAssignments
        $assignments = [];
        $adminUser = User::where('role', 'admin')->first();
        $adminId = $adminUser ? $adminUser->id : 1;

        foreach ($facultyData as $data) {
            $user = User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'name' => $data['name'],
                    'email' => $data['username'] . '@scsa.local',
                    'password' => Hash::make('faculty123'),
                    'role' => 'faculty',
                    'must_change_password' => false,
                ]
            );

            $faculty = Faculty::firstOrCreate(
                ['employee_code' => $data['employee_code']],
                [
                    'user_id' => $user->id,
                    'department_id' => $department->id,
                    'designation' => 'Assistant Professor',
                ]
            );

            // Create Subject with 4 credits
            $subject = Subject::create([
                'program_id' => $program->id,
                'semester_id' => $semester->id,
                'subject_code' => $data['subject_code'],
                'subject_name' => $data['subject_name'],
                'credits' => 4,
                'status' => 'active',
            ]);

            // Create SubjectAssignment
            $assignment = SubjectAssignment::create([
                'faculty_id' => $faculty->id,
                'subject_id' => $subject->id,
                'class_section_id' => $classSection->id,
                'academic_year' => '2026-27',
                'status' => 'active',
                'external_marks_status' => 'submitted', // submitted so we can see final marksheet!
            ]);

            $assignments[] = [
                'assignment' => $assignment,
                'faculty_user' => $user,
            ];

            // Create components
            $c1 = InternalMarkComponent::create([
                'subject_assignment_id' => $assignment->id,
                'name' => 'Assignment 1',
                'max_marks' => 15.00,
            ]);
            $c2 = InternalMarkComponent::create([
                'subject_assignment_id' => $assignment->id,
                'name' => 'Quiz 1',
                'max_marks' => 15.00,
            ]);
        }

        $this->command->info("Created 4 subjects (4 credits each) and assigned them to Parth Patel, Jinal Bhatt, Sanyam Sheth, and Ankita Chauhan.");

        // 5. Fetch all students in ClassSection 1
        $students = Student::where('class_section_id', $classSection->id)->get();
        $totalStudentsCount = $students->count();
        $this->command->info("Total students in section: {$totalStudentsCount}");

        // Select 4 students for low attendance (20%)
        $lowAttendanceStudents = $students->take(4);
        $lowAttendanceIds = $lowAttendanceStudents->pluck('id')->toArray();
        $this->command->warn("Low Attendance Students (20%): " . implode(', ', $lowAttendanceStudents->map(fn($s) => $s->user->name)->toArray()));

        // Select 4 students to fail (< 40 marks, let's take indices 4, 5, 6, 7)
        $failStudents = $students->slice(4, 4);
        $failIds = $failStudents->pluck('id')->toArray();
        $this->command->warn("Failing Students (< 40 marks): " . implode(', ', $failStudents->map(fn($s) => $s->user->name)->toArray()));

        // Delete existing attendance & lecture sessions to ensure clean cumulative percentage
        AttendanceRecord::query()->delete();
        LectureSession::query()->delete();

        // Seed 10 new lecture sessions
        $sessions = [];
        $firstAssignment = $assignments[0]['assignment'];
        for ($i = 1; $i <= 10; $i++) {
            $sessions[] = LectureSession::create([
                'subject_assignment_id' => $firstAssignment->id,
                'lecture_date' => now()->subDays(11 - $i)->toDateString(),
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'lecture_no' => 1,
                'session_type' => 'regular',
                'status' => 'conducted',
                'submitted_at' => now(),
            ]);
        }

        // Record attendance (20% for first 4, 100% for the rest)
        foreach ($sessions as $sessionIndex => $session) {
            foreach ($students as $student) {
                if (in_array($student->id, $lowAttendanceIds)) {
                    $status = ($sessionIndex < 2) ? 'present' : 'absent';
                } else {
                    $status = 'present';
                }

                AttendanceRecord::create([
                    'lecture_session_id' => $session->id,
                    'student_id' => $student->id,
                    'status' => $status,
                    'marked_by' => $assignments[0]['faculty_user']->id,
                    'marked_at' => now(),
                ]);
            }
        }

        $this->command->info("Seeded 10 lecture sessions. 4 students set to exactly 20% attendance.");

        // 9. Seed internal & external marks for all 4 subjects
        foreach ($assignments as $aData) {
            $assignment = $aData['assignment'];
            $facultyUser = $aData['faculty_user'];

            $components = InternalMarkComponent::where('subject_assignment_id', $assignment->id)->get();
            $c1 = $components[0];
            $c2 = $components[1];

            foreach ($students as $student) {
                $isFail = in_array($student->id, $failIds);

                if ($isFail) {
                    // Failing marks: CIE = 12, External = 15, Mid Sem Raw = 15 (Scaled = 10)
                    // Total marks = 10 + 12 + 15 = 37 out of 100 (FAIL - F)
                    $midSemRaw = 15.00;
                    $midSemScaled = 10.00;
                    $comp1Marks = 6.00;
                    $comp2Marks = 6.00;
                    $cie30 = 12.00;
                    $total50 = 22.00;
                    $external50 = 15.00;
                    $total100 = 37.00;
                } else {
                    // Passing marks: CIE = 25, External = 38, Mid Sem Raw = 22.5 (Scaled = 15)
                    // Total marks = 15 + 25 + 38 = 78 out of 100 (PASS - A+)
                    $midSemRaw = 22.50;
                    $midSemScaled = 15.00;
                    $comp1Marks = 12.50;
                    $comp2Marks = 12.50;
                    $cie30 = 25.00;
                    $total50 = 40.00;
                    $external50 = 38.00;
                    $total100 = 78.00;
                }

                // Create InternalMark record
                $internalMark = InternalMark::create([
                    'subject_assignment_id' => $assignment->id,
                    'student_id' => $student->id,
                    'mid_sem_30' => $midSemRaw,
                    'mid_sem_20' => $midSemScaled,
                    'cie_30' => $cie30,
                    'total_50' => $total50,
                    'external_50' => $external50,
                    'total_100' => $total100,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'marked_by' => $facultyUser->id,
                ]);

                // Create InternalMarkValues
                InternalMarkValue::create([
                    'internal_mark_id' => $internalMark->id,
                    'internal_marks_component_id' => $c1->id,
                    'marks_obtained' => $comp1Marks,
                ]);
                InternalMarkValue::create([
                    'internal_mark_id' => $internalMark->id,
                    'internal_marks_component_id' => $c2->id,
                    'marks_obtained' => $comp2Marks,
                ]);
            }
        }

        $this->command->info("Seeded marks for all students across 4 subjects. 4 students set to fail (< 40 marks).");
        $this->command->info("=== DATA GENERATION COMPLETE ===");
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
