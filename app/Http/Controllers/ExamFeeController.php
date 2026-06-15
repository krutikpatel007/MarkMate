<?php

namespace App\Http\Controllers;

use App\Models\ExamFee;
use App\Models\ExamFeePayment;
use App\Models\Student;
use App\Models\Semester;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamFeeController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user->isStudent(), 403);

        $student = Student::with(['semester.program'])->where('user_id', $user->id)->firstOrFail();
        $examFee = ExamFee::where('semester_id', $student->semester_id)->first();
        
        $payment = null;
        if ($examFee) {
            $payment = ExamFeePayment::where('student_id', $student->id)
                ->where('exam_fee_id', $examFee->id)
                ->first();
        }

        $pastPayments = ExamFeePayment::with(['examFee.semester.program'])
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();

        return view('student.fees.index', compact('student', 'examFee', 'payment', 'pastPayments'));
    }

    public function pay(Request $request, ExamFee $examFee): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isStudent(), 403);

        $student = Student::where('user_id', $user->id)->firstOrFail();
        
        $request->validate([
            'payment_method' => ['required', 'string', 'in:card,upi'],
            'card_number' => ['required_if:payment_method,card', 'string', 'nullable'],
            'upi_id' => ['required_if:payment_method,upi', 'string', 'nullable'],
        ]);

        $reference = 'TXN-' . strtoupper(Str::random(10));

        ExamFeePayment::updateOrCreate(
            [
                'student_id' => $student->id,
                'exam_fee_id' => $examFee->id,
            ],
            [
                'amount_paid' => $examFee->amount,
                'payment_method' => 'online',
                'status' => 'paid',
                'transaction_reference' => $reference,
                'paid_at' => now(),
            ]
        );

        return redirect()
            ->route('exam-fees.index')
            ->with('status', 'Exam fee of ₹' . number_format($examFee->amount, 2) . ' paid successfully. Hall ticket cleared.');
    }

    public function receipt(ExamFeePayment $payment): View
    {
        $user = Auth::user();
        
        if ($user->isStudent()) {
            $student = Student::where('user_id', $user->id)->firstOrFail();
            abort_unless((int)$payment->student_id === (int)$student->id, 403);
        } else {
            abort_unless($user->isAdmin() || $user->isFeesDept(), 403);
        }

        $payment->load(['student.user', 'student.program', 'student.semester', 'examFee.semester']);

        return view('student.fees.receipt', compact('payment'));
    }

    public function adminIndex(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isFeesDept(), 403);

        $semesters = Semester::with('program')->get()->sortBy('program.program_code');
        $feeConfigs = ExamFee::with('semester.program')->get()->groupBy('semester.semester_no');

        $query = ExamFeePayment::with(['student.user', 'examFee.semester.program', 'verifiedBy'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('enrollment_no', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $recentPayments = $query->take(50)->get();

        return view('exam.fees.index', compact('semesters', 'feeConfigs', 'recentPayments'));
    }

    public function storeConfig(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isFeesDept(), 403);

        $request->validate([
            'semester_no' => ['required', 'integer', 'min:1', 'max:10'],
            'due_date' => ['required', 'date'],
        ]);

        $semesters = Semester::where('semester_no', $request->semester_no)->get();

        if ($semesters->isEmpty()) {
            return back()->withErrors(['semester_no' => 'No active semesters found with this semester number.']);
        }

        foreach ($semesters as $sem) {
            ExamFee::updateOrCreate(
                ['semester_id' => $sem->id],
                [
                    'amount' => 1000.00, // Fixed 1000 per sem for all courses
                    'due_date' => $request->due_date,
                ]
            );
        }

        return redirect()
            ->route('exam-fees.admin.index')
            ->with('status', "Exam fee configuration (₹1,000.00) applied successfully to all courses for Semester {$request->semester_no}.");
    }

    public function manualPay(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isFeesDept(), 403);

        $request->validate([
            'enrollment_no' => ['required', 'string', 'exists:students,enrollment_no'],
            'payment_reference' => ['required', 'string', 'max:255'],
        ]);

        $student = Student::where('enrollment_no', $request->enrollment_no)->firstOrFail();
        $examFee = ExamFee::where('semester_id', $student->semester_id)->first();

        if (!$examFee) {
            return back()->withErrors(['enrollment_no' => 'No exam fee configured for the student\'s semester.']);
        }

        ExamFeePayment::updateOrCreate(
            [
                'student_id' => $student->id,
                'exam_fee_id' => $examFee->id,
            ],
            [
                'amount_paid' => $examFee->amount,
                'payment_method' => 'manual',
                'status' => 'paid',
                'transaction_reference' => $request->payment_reference,
                'paid_at' => now(),
                'verified_by' => $user->id,
            ]
        );

        return redirect()
            ->route('exam-fees.admin.index')
            ->with('status', "Manual exam fee payment successfully recorded for student {$student->enrollment_no}.");
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isFeesDept(), 403);

        $query = ExamFeePayment::with(['student.user', 'examFee.semester.program', 'verifiedBy'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('enrollment_no', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->get();

        $headers = [
            'Enrollment No',
            'Roll No',
            'Student Name',
            'Program & Semester',
            'Amount Paid',
            'Method',
            'Reference',
            'Paid At',
            'Verified By',
        ];

        $filename = 'exam-fee-transactions-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($headers, $payments) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headers);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    $payment->student->enrollment_no,
                    $payment->student->roll_no,
                    $payment->student->user->name,
                    $payment->examFee->semester->program->program_code . ' Sem ' . $payment->examFee->semester->semester_no,
                    $payment->amount_paid,
                    ucfirst($payment->payment_method),
                    $payment->transaction_reference,
                    $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : '-',
                    $payment->verifiedBy ? $payment->verifiedBy->name : '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function defaulters(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isFeesDept(), 403);

        $query = Student::query()
            ->with(['user', 'semester.program'])
            ->whereHas('semester.examFee')
            ->whereDoesntHave('examFeePayments', function($q) {
                $q->where('status', 'paid');
            });

        if ($request->filled('department_id')) {
            $query->whereHas('semester.program', function ($q) use ($request) {
                $q->where('department_id', $request->integer('department_id'));
            });
        }

        if ($request->filled('program_id')) {
            $query->whereHas('semester', function ($q) use ($request) {
                $q->where('program_id', $request->integer('program_id'));
            });
        }

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->integer('semester_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('enrollment_no', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $defaulters = $query->orderBy('enrollment_no')->get();

        $departments = Department::where('status', 'active')->orderBy('department_name')->get();
        $programs = Program::where('status', 'active')->orderBy('program_name')->get();
        $semesters = Semester::with('program')->get()->sortBy('semester_no');

        return view('exam.fees.defaulters', [
            'defaulters' => $defaulters,
            'departments' => $departments,
            'programs' => $programs,
            'semesters' => $semesters,
            'filterDepartmentId' => $request->integer('department_id') ?: null,
            'filterProgramId' => $request->integer('program_id') ?: null,
            'filterSemesterId' => $request->integer('semester_id') ?: null,
            'filterSearch' => $request->input('search') ?: null,
        ]);
    }

    public function exportDefaulters(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isFeesDept(), 403);

        $query = Student::query()
            ->with(['user', 'semester.program'])
            ->whereHas('semester.examFee')
            ->whereDoesntHave('examFeePayments', function($q) {
                $q->where('status', 'paid');
            });

        if ($request->filled('department_id')) {
            $query->whereHas('semester.program', function ($q) use ($request) {
                $q->where('department_id', $request->integer('department_id'));
            });
        }

        if ($request->filled('program_id')) {
            $query->whereHas('semester', function ($q) use ($request) {
                $q->where('program_id', $request->integer('program_id'));
            });
        }

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->integer('semester_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('enrollment_no', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $defaulters = $query->orderBy('enrollment_no')->get();

        $headers = [
            'Enrollment No',
            'Roll No',
            'Student Name',
            'Program & Semester',
            'Outstanding Amount',
            'Due Date',
        ];

        $filename = 'exam-fee-defaulters-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($headers, $defaulters) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headers);

            foreach ($defaulters as $student) {
                $examFee = ExamFee::where('semester_id', $student->semester_id)->first();
                fputcsv($handle, [
                    $student->enrollment_no,
                    $student->roll_no ?: '-',
                    $student->user->name,
                    $student->semester->program->program_code . ' Sem ' . $student->semester->semester_no,
                    $examFee ? $examFee->amount : 1000.00,
                    $examFee && $examFee->due_date ? $examFee->due_date->format('Y-m-d') : '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function voidPayment(ExamFeePayment $payment): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isFeesDept(), 403);

        $enrollment = $payment->student->enrollment_no;
        $payment->delete();

        return redirect()
            ->route('exam-fees.admin.index')
            ->with('status', "Manual fee payment for student {$enrollment} has been voided successfully.");
    }

    public function exportDCR(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isFeesDept(), 403);

        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'payment_method' => ['nullable', 'string', 'in:all,online,manual'],
        ]);

        $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
        $method = $request->input('payment_method');

        $query = ExamFeePayment::with(['student.user', 'examFee.semester.program', 'verifiedBy'])
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->where('status', 'paid');

        if ($method && $method !== 'all' && $method !== '') {
            $query->where('payment_method', $method);
        }

        $payments = $query->orderBy('paid_at', 'asc')->get();

        $headers = [
            'Transaction Date',
            'Enrollment No',
            'Student Name',
            'Program & Semester',
            'Payment Method',
            'Transaction Reference',
            'Amount Paid',
            'Verified By',
        ];

        $filename = 'daily-collections-report-' . $startDate->format('Ymd') . '-to-' . $endDate->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($headers, $payments) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headers);

            $total = 0;
            foreach ($payments as $payment) {
                fputcsv($handle, [
                    $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : '-',
                    $payment->student->enrollment_no,
                    $payment->student->user->name,
                    $payment->examFee->semester->program->program_code . ' Sem ' . $payment->examFee->semester->semester_no,
                    ucfirst($payment->payment_method),
                    $payment->transaction_reference,
                    $payment->amount_paid,
                    $payment->verifiedBy ? $payment->verifiedBy->name : ($payment->payment_method === 'online' ? 'System' : 'Admin'),
                ]);
                $total += $payment->amount_paid;
            }

            // Append total row
            fputcsv($handle, []);
            fputcsv($handle, ['Total Collection', '', '', '', '', '', $total, '']);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
