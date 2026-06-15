<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Models\Semester;
use App\Models\ExamFee;
use App\Models\ExamFeePayment;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExamFeesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function createFeesUser(): User
    {
        $feesDept = Department::firstOrCreate(
            ['department_code' => 'FEES'],
            ['department_name' => 'Fees Department']
        );
        
        $feesUser = User::create([
            'name' => 'Fees Manager',
            'username' => 'fees_manager',
            'email' => 'fees@shreyarthuni.ac.in',
            'password' => Hash::make('fees123'),
            'role' => 'fees',
            'must_change_password' => false,
        ]);

        Faculty::create([
            'user_id' => $feesUser->id,
            'department_id' => $feesDept->id,
            'employee_code' => 'FEES-MGR-01',
            'designation' => 'Fees Administrator',
        ]);

        return $feesUser;
    }

    public function test_fees_dept_can_configure_exam_fee_but_others_blocked(): void
    {
        $this->withoutVite();

        $feesUser = $this->createFeesUser();
        $examHod = User::where('username', 'exam_hod')->firstOrFail();
        $semester = Semester::firstOrFail();

        // 1. Non-fees user is blocked
        $response = $this->actingAs($examHod)
            ->post(route('exam-fees.admin.store-config'), [
                'semester_no' => $semester->semester_no,
                'due_date' => now()->addMonth()->format('Y-m-d'),
            ]);
        $response->assertStatus(403);

        // 2. Fees user is allowed
        $response = $this->actingAs($feesUser)
            ->post(route('exam-fees.admin.store-config'), [
                'semester_no' => $semester->semester_no,
                'due_date' => now()->addMonth()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('exam_fees', [
            'semester_id' => $semester->id,
            'amount' => 1000.00, // Enforces 1000.00 flat rate
        ]);
    }

    public function test_student_can_view_exam_fees_and_pay_via_simulator(): void
    {
        $this->withoutVite();

        $student = Student::with('user')->firstOrFail();
        $studentUser = $student->user;
        $studentUser->must_change_password = false;
        $studentUser->save();

        // 1. Setup exam fee config
        $examFee = ExamFee::create([
            'semester_id' => $student->semester_id,
            'amount' => 1000.00,
            'due_date' => now()->addWeeks(2),
        ]);

        // 2. Student views fee center
        $response = $this->actingAs($studentUser)
            ->get(route('exam-fees.index'))
            ->assertOk()
            ->assertSeeText('₹1,000.00')
            ->assertSeeText('Pending End-Semester Exam Fee')
            ->assertSeeText('Unpaid');

        // 3. Student pays via Card
        $response = $this->actingAs($studentUser)
            ->post(route('exam-fees.pay', $examFee->id), [
                'payment_method' => 'card',
                'card_number' => '1234567812345678',
            ]);

        $response->assertRedirect(route('exam-fees.index'));
        
        $this->assertDatabaseHas('exam_fee_payments', [
            'student_id' => $student->id,
            'exam_fee_id' => $examFee->id,
            'amount_paid' => 1000.00,
            'payment_method' => 'online',
            'status' => 'paid',
        ]);

        // 4. Student views receipt
        $payment = ExamFeePayment::where('student_id', $student->id)->firstOrFail();
        $response = $this->actingAs($studentUser)
            ->get(route('exam-fees.receipt', $payment->id))
            ->assertOk()
            ->assertSeeText('E-Transaction Copy')
            ->assertSeeText($payment->transaction_reference);
    }

    public function test_fees_dept_can_record_manual_payment_but_others_blocked(): void
    {
        $this->withoutVite();

        $feesUser = $this->createFeesUser();
        $examHod = User::where('username', 'exam_hod')->firstOrFail();
        $student = Student::with('user')->firstOrFail();

        // Setup exam fee config
        $examFee = ExamFee::create([
            'semester_id' => $student->semester_id,
            'amount' => 1000.00,
            'due_date' => now()->addWeeks(2),
        ]);

        // 1. Non-fees user is blocked
        $response = $this->actingAs($examHod)
            ->post(route('exam-fees.admin.manual-pay'), [
                'enrollment_no' => $student->enrollment_no,
                'payment_reference' => 'CHALLAN-12345',
            ]);
        $response->assertStatus(403);

        // 2. Fees user is allowed
        $response = $this->actingAs($feesUser)
            ->post(route('exam-fees.admin.manual-pay'), [
                'enrollment_no' => $student->enrollment_no,
                'payment_reference' => 'CHALLAN-12345',
            ]);

        $response->assertRedirect(route('exam-fees.admin.index'));

        $this->assertDatabaseHas('exam_fee_payments', [
            'student_id' => $student->id,
            'exam_fee_id' => $examFee->id,
            'amount_paid' => 1000.00,
            'payment_method' => 'manual',
            'status' => 'paid',
            'transaction_reference' => 'CHALLAN-12345',
            'verified_by' => $feesUser->id,
        ]);
    }

    public function test_unpaid_exam_fee_locks_student_hall_ticket(): void
    {
        $this->withoutVite();

        $student = Student::with('user')->firstOrFail();
        $studentUser = $student->user;
        $studentUser->must_change_password = false;
        $studentUser->save();

        // 1. Setup exam fee config
        $examFee = ExamFee::create([
            'semester_id' => $student->semester_id,
            'amount' => 1000.00,
            'due_date' => now()->addWeeks(2),
        ]);

        // Note: student has 100% attendance (seeded), but fee is unpaid.
        // Let's assert that student cannot download or view hall ticket.
        $response = $this->actingAs($studentUser)
            ->get(route('student.hall-ticket.show'))
            ->assertOk()
            ->assertSeeText('Blocked')
            ->assertSeeText('Unpaid');

        $response = $this->actingAs($studentUser)
            ->get(route('student.hall-ticket.download'))
            ->assertStatus(403);

        // 2. Pay the fee
        ExamFeePayment::create([
            'student_id' => $student->id,
            'exam_fee_id' => $examFee->id,
            'amount_paid' => 1000.00,
            'payment_method' => 'online',
            'status' => 'paid',
            'transaction_reference' => 'TXN-OK-123',
            'paid_at' => now(),
        ]);

        // 3. Hall ticket is unlocked immediately
        $response = $this->actingAs($studentUser)
            ->get(route('student.hall-ticket.show'))
            ->assertOk()
            ->assertSeeText('Clearance Certificate Issued');

        $response = $this->actingAs($studentUser)
            ->get(route('student.hall-ticket.download'))
            ->assertOk();
    }

    public function test_fees_dept_can_export_payments_to_csv_but_others_blocked(): void
    {
        $this->withoutVite();

        $feesUser = $this->createFeesUser();
        $examHod = User::where('username', 'exam_hod')->firstOrFail();

        // 1. Non-fees user is blocked
        $response = $this->actingAs($examHod)
            ->get(route('exam-fees.admin.export'))
            ->assertStatus(403);

        // 2. Fees user is allowed
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_fees_dept_can_view_dashboard_with_fees_data(): void
    {
        $this->withoutVite();

        $feesUser = $this->createFeesUser();

        $response = $this->actingAs($feesUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('isFeesDept', true)
            ->assertSeeText('Total Collected')
            ->assertSeeText('Cleared Demands')
            ->assertSeeText('Defaulters')
            ->assertSeeText('Recent Fee Payments');
    }

    public function test_fees_dept_can_manage_defaulters_and_reports_and_void_payments(): void
    {
        $this->withoutVite();

        $feesUser = $this->createFeesUser();
        $student = Student::firstOrFail();

        // 1. Setup exam fee config
        $examFee = ExamFee::create([
            'semester_id' => $student->semester_id,
            'amount' => 1000.00,
            'due_date' => now()->addWeeks(2),
        ]);

        // Student is now a defaulter since fee is configured but unpaid
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.defaulters'))
            ->assertOk()
            ->assertSeeText($student->enrollment_no);

        // Test searching defaulters by enrollment
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.defaulters', ['search' => $student->enrollment_no]))
            ->assertOk()
            ->assertSeeText($student->enrollment_no);

        // Test searching defaulters by name
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.defaulters', ['search' => $student->user->name]))
            ->assertOk()
            ->assertSeeText($student->enrollment_no);

        // Test searching defaulters with nonexistent value
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.defaulters', ['search' => 'NONEXISTENT_ENROLLMENT']))
            ->assertOk();
        $this->assertStringNotContainsString('<td><strong>' . $student->enrollment_no . '</strong></td>', $response->getContent());

        // Export defaulters (unfiltered)
        ob_start();
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.defaulters.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->sendContent();
        $output = ob_get_clean();
        $this->assertStringContainsString($student->enrollment_no, $output);

        // Test exporting defaulters with search filter (matching)
        ob_start();
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.defaulters.export', ['search' => $student->enrollment_no]))
            ->assertOk();
        $response->sendContent();
        $output = ob_get_clean();
        $this->assertStringContainsString($student->enrollment_no, $output);

        // Test exporting defaulters with search filter (non-matching)
        ob_start();
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.defaulters.export', ['search' => 'NONEXISTENT_ENROLLMENT']))
            ->assertOk();
        $response->sendContent();
        $output = ob_get_clean();
        $this->assertStringNotContainsString($student->enrollment_no, $output);

        // 2. Clear fee manually
        $this->actingAs($feesUser)
            ->post(route('exam-fees.admin.manual-pay'), [
                'enrollment_no' => $student->enrollment_no,
                'payment_reference' => 'TEST-CHALLAN-999',
            ])
            ->assertRedirect(route('exam-fees.admin.index'));

        $this->assertDatabaseHas('exam_fee_payments', [
            'student_id' => $student->id,
            'exam_fee_id' => $examFee->id,
            'status' => 'paid',
        ]);

        // Student should no longer show in the defaulters list
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.defaulters'));
        $response->assertOk();
        $this->assertStringNotContainsString('<td><strong>' . $student->enrollment_no . '</strong></td>', $response->getContent());

        // Test searching recent transactions by enrollment
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.index', ['search' => $student->enrollment_no]))
            ->assertOk()
            ->assertSeeText($student->enrollment_no);

        // Test searching recent transactions by name
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.index', ['search' => $student->user->name]))
            ->assertOk()
            ->assertSeeText($student->enrollment_no);

        // Test searching recent transactions with nonexistent value
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.index', ['search' => 'NONEXISTENT_ENROLLMENT']))
            ->assertOk();
        $this->assertStringNotContainsString('<td><strong>' . $student->enrollment_no . '</strong></td>', $response->getContent());

        // Test exporting transactions with search filter (matching)
        ob_start();
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.export', ['search' => $student->enrollment_no]))
            ->assertOk();
        $response->sendContent();
        $output = ob_get_clean();
        $this->assertStringContainsString($student->enrollment_no, $output);

        // Test exporting transactions with search filter (non-matching)
        ob_start();
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.export', ['search' => 'NONEXISTENT_ENROLLMENT']))
            ->assertOk();
        $response->sendContent();
        $output = ob_get_clean();
        $this->assertStringNotContainsString($student->enrollment_no, $output);

        // 3. Export Daily Collections Report (DCR)
        ob_start();
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.reports.dcr', [
                'start_date' => now()->subDay()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
                'payment_method' => 'all',
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->sendContent();
        $output = ob_get_clean();
        $this->assertStringContainsString('TEST-CHALLAN-999', $output);

        // 4. Void the manual payment
        $payment = ExamFeePayment::where('student_id', $student->id)->firstOrFail();
        $this->actingAs($feesUser)
            ->delete(route('exam-fees.admin.payments.void', $payment->id))
            ->assertRedirect(route('exam-fees.admin.index'));

        // Payment record is deleted
        $this->assertDatabaseMissing('exam_fee_payments', ['id' => $payment->id]);

        // Student is a defaulter again
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.defaulters'))
            ->assertOk()
            ->assertSeeText($student->enrollment_no);
    }

    public function test_dcr_report_works_without_explicit_payment_method(): void
    {
        $this->withoutVite();
        $feesUser = $this->createFeesUser();
        $student = Student::firstOrFail();
        $examFee = ExamFee::create([
            'semester_id' => $student->semester_id,
            'amount' => 1000.00,
            'due_date' => now()->addWeeks(2),
        ]);

        $payment = ExamFeePayment::create([
            'student_id' => $student->id,
            'exam_fee_id' => $examFee->id,
            'amount_paid' => 1000.00,
            'payment_method' => 'manual',
            'status' => 'paid',
            'transaction_reference' => 'DCR-TEST-999',
            'paid_at' => now(),
        ]);

        ob_start();
        $response = $this->actingAs($feesUser)
            ->get(route('exam-fees.admin.reports.dcr', [
                'start_date' => now()->subDay()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->sendContent();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('DCR-TEST-999', $output);
    }
}
