<?php

namespace Database\Seeders;

use App\Models\ClassSection;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\FacultyAppraisal;
use App\Models\FacultyFeedback;
use App\Models\FacultyLeaveRequest;
use App\Models\FacultyPayslip;
use App\Models\FacultySalaryConfig;
use App\Models\Student;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Models\ExamFee;
use App\Models\ExamFeePayment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RichDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Fetch seeded entities
        $students = Student::all();
        $faculties = Faculty::all();
        $assignments = SubjectAssignment::where('status', 'active')->get();
        $semesters = \App\Models\Semester::all();
        $admin = User::where('role', 'admin')->first();

        // Clear tables to prevent duplicates
        FacultyFeedback::query()->delete();
        FacultyLeaveRequest::query()->delete();
        \App\Models\Notice::query()->delete();

        // 3. Seed Faculty Salary Configs and Payslips
        foreach ($faculties as $f) {
            // Seed Salary Config
            $config = FacultySalaryConfig::updateOrCreate(
                ['faculty_id' => $f->id],
                [
                    'basic_pay' => rand(40, 80) * 1000,
                    'hra' => rand(8, 15) * 1000,
                    'da' => rand(4, 8) * 1000,
                    'special_allowance' => rand(1, 5) * 1000,
                    'deductions' => rand(2, 6) * 1000,
                ]
            );

            // Seed Payslips for the last 3 months
            for ($i = 1; $i <= 3; $i++) {
                $month = now()->subMonths($i)->month;
                $year = now()->subMonths($i)->year;

                $netSalary = $config->basic_pay + $config->hra + $config->da + $config->special_allowance - $config->deductions;

                FacultyPayslip::updateOrCreate(
                    [
                        'faculty_id' => $f->id,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'basic_pay' => $config->basic_pay,
                        'hra' => $config->hra,
                        'da' => $config->da,
                        'special_allowance' => $config->special_allowance,
                        'deductions' => $config->deductions,
                        'net_salary' => max(0, $netSalary),
                        'status' => 'paid',
                        'paid_at' => now()->subMonths($i)->startOfMonth()->addDays(5),
                    ]
                );
            }

            // Seed Appraisal
            FacultyAppraisal::updateOrCreate(
                [
                    'faculty_id' => $f->id,
                    'academic_year' => '2025-26',
                ],
                [
                    'reviewer_id' => $admin->id,
                    'score_teaching' => rand(80, 95),
                    'score_research' => rand(70, 90),
                    'score_administrative' => rand(75, 95),
                    'overall_rating' => round(rand(35, 50) / 10, 1),
                    'review_comments' => 'Demonstrated excellent pedagogical capabilities and active academic engagement.',
                ]
            );
        }

        // 4. Seed Faculty Feedback (from students)
        foreach ($assignments as $assignment) {
            foreach ($students as $student) {
                if ($student->class_section_id === $assignment->class_section_id) {
                    FacultyFeedback::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'subject_assignment_id' => $assignment->id,
                        ],
                        [
                            'rating' => rand(4, 5),
                            'comments' => 'Very clear explanation and helpful doubt solving sessions.',
                        ]
                    );
                }
            }
        }

        // 5. Seed Faculty Leave Requests (Pending & Approved)
        foreach ($faculties->take(3) as $f) {
            // One Approved Leave
            FacultyLeaveRequest::create([
                'faculty_id' => $f->id,
                'leave_type' => 'sick',
                'start_date' => now()->subDays(15),
                'end_date' => now()->subDays(14),
                'reason' => 'Recovery from seasonal flu.',
                'status' => 'approved',
                'decision_note' => 'Approved. Take rest.',
                'approved_at' => now()->subDays(16),
                'approved_by' => $admin->id,
            ]);

            // One Pending Leave
            FacultyLeaveRequest::create([
                'faculty_id' => $f->id,
                'leave_type' => 'casual',
                'start_date' => now()->addDays(5),
                'end_date' => now()->addDays(6),
                'reason' => 'Attending family wedding ceremony.',
                'status' => 'pending',
            ]);
        }

        // 6. Seed Exam Fees and Payments
        foreach ($semesters as $sem) {
            $fee = ExamFee::updateOrCreate(
                ['semester_id' => $sem->id],
                [
                    'amount' => 1000.00,
                    'due_date' => now()->addDays(30),
                ]
            );

            // Payments: 3 paid students, 2 unpaid/defaulters
            $paidStudents = $students->take(3);
            foreach ($paidStudents as $st) {
                if ($st->semester_id === $sem->id) {
                    ExamFeePayment::updateOrCreate(
                        [
                            'exam_fee_id' => $fee->id,
                            'student_id' => $st->id,
                        ],
                        [
                            'amount_paid' => 1000.00,
                            'payment_method' => 'online',
                            'transaction_reference' => 'TXN' . strtoupper(uniqid()),
                            'status' => 'paid',
                            'paid_at' => now()->subDays(5),
                        ]
                    );
                }
            }
        }

        \App\Models\Notice::create([
            'title' => 'Exam Registration Open',
            'message' => 'Exam registration for BCA Semester 1 is now open. Please clear your dues and register online.',
            'audience_type' => 'global',
            'author_id' => $admin->id,
        ]);

        \App\Models\Notice::create([
            'title' => 'Faculty Meeting Agenda',
            'message' => 'A monthly faculty meeting is scheduled for next Monday at 3:00 PM to review weekly loads.',
            'audience_type' => 'department_faculty',
            'audience_id' => $faculties->first()->department_id,
            'author_id' => $admin->id,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
