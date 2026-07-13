<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\ClassSection;
use App\Models\Department;
use App\Models\ExtraLectureRequest;
use App\Models\Faculty;
use App\Models\InAppNotification;
use App\Models\LectureSession;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ============================================================
        // SAFETY GUARD — Prevents accidental wipe of real data
        // ============================================================
        $studentCount = \App\Models\Student::count();
        if ($studentCount > 10) {
            $this->command->error('');
            $this->command->error('  ⚠  REAL DATA DETECTED — Seeder Aborted!');
            $this->command->error("  ⚠  Found {$studentCount} students in the database.");
            $this->command->error('  ⚠  Running this seeder would DELETE all real data.');
            $this->command->error('');
            $this->command->warn('  To reset to demo data intentionally, first backup then run:');
            $this->command->warn('  php artisan db:backup --label=before-reset');
            $this->command->warn('  php artisan migrate:fresh --seed --force');
            $this->command->error('');
            return;
        }
        // ============================================================

        $admin = User::create([
            'name' => 'Krutik Patel',
            'username' => 'admin',
            'email' => 'admin@shreyarthuni.ac.in',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $hodUser = User::create([
            'name' => 'Dr. Meera Shah',
            'username' => 'hod',
            'email' => 'hod@scsa.local',
            'password' => Hash::make('hod123'),
            'role' => 'hod',
            'must_change_password' => false,
        ]);

        $facultyUser = User::create([
            'name' => 'Prof. Arjun Desai',
            'username' => 'faculty',
            'email' => 'faculty@scsa.local',
            'password' => Hash::make('faculty123'),
            'role' => 'faculty',
            'must_change_password' => false,
        ]);

        $department = Department::create([
            'department_code' => 'SCSA',
            'department_name' => 'School of Computer Science and Applications',
        ]);

        $examDept = Department::create([
            'department_code' => 'EXAM',
            'department_name' => 'Examination Department',
            'status' => 'active',
        ]);

        $examHodUser = User::create([
            'name' => 'Dr. Kirit Vyas',
            'username' => 'exam_hod',
            'email' => 'exam.hod@shreyarthuni.ac.in',
            'password' => Hash::make('exam123'),
            'role' => 'hod',
            'must_change_password' => false,
        ]);

        Faculty::create([
            'user_id' => $examHodUser->id,
            'department_id' => $examDept->id,
            'employee_code' => 'EXAM-HOD-001',
            'designation' => 'Controller of Examinations',
        ]);

        $examStaffUser = User::create([
            'name' => 'Mr. Rajan Shah',
            'username' => 'exam_staff',
            'email' => 'exam.staff@shreyarthuni.ac.in',
            'password' => Hash::make('exam123'),
            'role' => 'faculty',
            'must_change_password' => false,
        ]);

        Faculty::create([
            'user_id' => $examStaffUser->id,
            'department_id' => $examDept->id,
            'employee_code' => 'EXAM-FAC-001',
            'designation' => 'Assistant Registrar (Exams)',
        ]);

        $feesDept = Department::create([
            'department_code' => 'FEES',
            'department_name' => 'Fees Department',
            'status' => 'active',
        ]);

        $feesStaffUser = User::create([
            'name' => 'Mrs. Asha Sharma',
            'username' => 'fees_staff',
            'email' => 'fees.staff@shreyarthuni.ac.in',
            'password' => Hash::make('fees123'),
            'role' => 'fees',
            'must_change_password' => false,
        ]);

        Faculty::create([
            'user_id' => $feesStaffUser->id,
            'department_id' => $feesDept->id,
            'employee_code' => 'FEES-STAFF-001',
            'designation' => 'Fees Administrator',
        ]);

        $hrDept = Department::create([
            'department_code' => 'HR',
            'department_name' => 'Human Resource Department',
            'status' => 'active',
        ]);

        $hrStaffUser = User::create([
            'name' => 'Mr. Anil Mehta',
            'username' => 'hr_staff',
            'email' => 'hr.staff@shreyarthuni.ac.in',
            'password' => Hash::make('hr123'),
            'role' => 'hr',
            'must_change_password' => false,
        ]);

        Faculty::create([
            'user_id' => $hrStaffUser->id,
            'department_id' => $hrDept->id,
            'employee_code' => 'HR-STAFF-001',
            'designation' => 'HR Manager',
        ]);

        Faculty::create([
            'user_id' => $hodUser->id,
            'department_id' => $department->id,
            'employee_code' => 'SCSA-HOD-001',
            'designation' => 'Head of Department',
        ]);

        $faculty = Faculty::create([
            'user_id' => $facultyUser->id,
            'department_id' => $department->id,
            'employee_code' => 'SCSA-FAC-001',
            'designation' => 'Assistant Professor',
        ]);

        if (!app()->environment('testing', 'local')) {
            $programs = [
                'BCA' => 'Bachelor of Computer Applications',
                'IMSC' => 'Integrated M.Sc',
                'BSc' => 'Bachelor of Science',
                'MCA' => 'Master of Computer Applications',
                'Diploma' => 'Diploma'
            ];

            foreach ($programs as $code => $name) {
                $program = Program::create([
                    'department_id' => $department->id,
                    'program_code' => $code,
                    'program_name' => $name,
                    'status' => 'active',
                ]);

                for ($sem = 1; $sem <= 10; $sem++) {
                    Semester::create([
                        'program_id' => $program->id,
                        'semester_no' => $sem,
                        'semester_name' => 'Semester ' . $sem,
                        'status' => 'active',
                    ]);
                }
            }
        }

        if (app()->environment('testing', 'local')) {
            $programDefinitions = [
                'BCA' => ['name' => 'Bachelor of Computer Applications', 'semesters' => [1 => 2, 3 => 2, 5 => 2]],
                'BSCIT' => ['name' => 'B.Sc IT', 'semesters' => [1 => 1, 3 => 1, 5 => 1]],
                'MCA' => ['name' => 'Master of Computer Applications', 'semesters' => [1 => 1, 3 => 1]],
                'MSCIT' => ['name' => 'M.Sc IT', 'semesters' => [1 => 1, 3 => 1]],
            ];

            $sections = [];
            foreach ($programDefinitions as $code => $definition) {
                $program = Program::create([
                    'department_id' => $department->id,
                    'program_code' => $code,
                    'program_name' => $definition['name'],
                ]);

                foreach ($definition['semesters'] as $semesterNo => $sectionCount) {
                    $semester = Semester::create([
                        'program_id' => $program->id,
                        'semester_no' => $semesterNo,
                        'semester_name' => 'Semester '.$semesterNo,
                    ]);

                    foreach (range(1, $sectionCount) as $index) {
                        $sectionName = chr(64 + $index);
                        $sections["{$code}-{$semesterNo}-{$sectionName}"] = ClassSection::create([
                            'program_id' => $program->id,
                            'semester_id' => $semester->id,
                            'section_name' => $sectionName,
                            'display_name' => "{$program->program_name} Sem {$semesterNo} {$sectionName}",
                        ]);
                    }
                }
            }

            $bcaSem1A = $sections['BCA-1-A'];
            $program = $bcaSem1A->program;
            $semester = $bcaSem1A->semester;

            [$firstLecture, $secondLecture, $lectureDate] = (new ShreyarthImageTimetableSeeder)->run(
                $bcaSem1A,
                $department,
                $facultyUser,
                $faculty,
            );

            $aplAssignment = SubjectAssignment::query()
                ->where('class_section_id', $bcaSem1A->id)
                ->whereHas('subject', fn ($q) => $q->where('subject_code', 'APL'))
                ->firstOrFail();

            $students = collect([
                ['Riya Patel', 'SU2026BCA001', '1'],
                ['Aarav Mehta', 'SU2026BCA002', '2'],
                ['Nisha Trivedi', 'SU2026BCA003', '3'],
                ['Dev Shah', 'SU2026BCA004', '4'],
                ['Krisha Joshi', 'SU2026BCA005', '5'],
            ])->map(function (array $studentData) use ($program, $semester, $bcaSem1A) {
                [$name, $enrollmentNo, $rollNo] = $studentData;

                $user = User::create([
                    'name' => $name,
                    'username' => $enrollmentNo,
                    'email' => null,
                    'password' => Hash::make('student123'),
                    'role' => 'student',
                    'must_change_password' => true,
                ]);

                return Student::create([
                    'user_id' => $user->id,
                    'program_id' => $program->id,
                    'semester_id' => $semester->id,
                    'class_section_id' => $bcaSem1A->id,
                    'enrollment_no' => $enrollmentNo,
                    'roll_no' => $rollNo,
                ]);
            });

            $conductedSession = LectureSession::create([
                'timetable_id' => $firstLecture->id,
                'subject_assignment_id' => $firstLecture->subject_assignment_id,
                'lecture_date' => $lectureDate,
                'start_time' => $firstLecture->start_time,
                'end_time' => $firstLecture->end_time,
                'lecture_no' => $firstLecture->lecture_no,
                'session_type' => 'regular',
                'status' => 'conducted',
                'submitted_at' => now(),
            ]);

            $scheduledSession = LectureSession::create([
                'timetable_id' => $secondLecture->id,
                'subject_assignment_id' => $secondLecture->subject_assignment_id,
                'lecture_date' => $lectureDate,
                'start_time' => $secondLecture->start_time,
                'end_time' => $secondLecture->end_time,
                'lecture_no' => $secondLecture->lecture_no,
                'session_type' => 'regular',
                'status' => 'scheduled',
            ]);

            foreach ($students as $index => $student) {
                AttendanceRecord::create([
                    'lecture_session_id' => $conductedSession->id,
                    'student_id' => $student->id,
                    'status' => $index === 2 ? 'absent_with_leave' : ($index === 3 ? 'absent' : 'present'),
                    'marked_by' => $facultyUser->id,
                    'marked_at' => now(),
                ]);
            }

            ExtraLectureRequest::create([
                'faculty_id' => $faculty->id,
                'subject_assignment_id' => $aplAssignment->id,
                'requested_date' => $lectureDate->copy()->addDay(),
                'start_time' => '12:00:00',
                'end_time' => '13:00:00',
                'session_type' => 'remedial',
                'reason' => 'Need a remedial lecture for programming practice.',
            ]);

            InAppNotification::create([
                'user_id' => $admin->id,
                'title' => 'SCSA setup completed',
                'message' => 'Starter academic data and demo attendance have been seeded.',
                'type' => 'success',
            ]);

            InAppNotification::create([
                'user_id' => $students[3]->user_id,
                'title' => 'Low attendance warning',
                'message' => 'Your APL attendance is below the 75% threshold.',
                'type' => 'warning',
            ]);

            unset($scheduledSession);
        }
    }
}
