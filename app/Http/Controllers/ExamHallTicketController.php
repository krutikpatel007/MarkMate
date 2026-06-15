<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ExamWaiver;
use App\Models\AttendanceRecord;
use App\Models\LectureSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExamHallTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';
        abort_unless($user->isAdmin() || $user->isCoe() || ($user->isHod() && $isExamDept), 403);

        // Get all students with their overall attendance
        $attendanceSummaries = AttendanceRecord::query()
            ->select([
                'students.id',
                'students.enrollment_no',
                'students.roll_no',
                'students.class_section_id',
                'students.program_id',
                'students.semester_id',
                'users.name',
                DB::raw("sum(case when attendance_records.status = 'present' then 1 else 0 end) as present_count"),
                DB::raw('count(*) as conducted_count'),
            ])
            ->join('students', 'students.id', '=', 'attendance_records.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->join('lecture_sessions', 'lecture_sessions.id', '=', 'attendance_records.lecture_session_id')
            ->whereIn('lecture_sessions.status', ['conducted', 'locked'])
            ->groupBy('students.id', 'students.enrollment_no', 'students.roll_no', 'students.class_section_id', 'students.program_id', 'students.semester_id', 'users.name')
            ->get()
            ->map(function ($row) {
                $row->percentage = $row->conducted_count > 0
                    ? round(($row->present_count / $row->conducted_count) * 100, 2)
                    : 0;
                return $row;
            });

        // Filter for defaulters (percentage < 75)
        $defaulters = $attendanceSummaries->filter(fn($s) => $s->percentage < 75)->values();

        // Load all exam fees
        $examFees = \App\Models\ExamFee::all()->keyBy('semester_id');
        
        // Load all fee payments for these students
        $studentIds = $defaulters->pluck('id')->toArray();
        $feePayments = \App\Models\ExamFeePayment::whereIn('student_id', $studentIds)
            ->where('status', 'paid')
            ->get()
            ->groupBy('student_id');

        $defaulters->each(function ($row) use ($examFees, $feePayments) {
            $feePaid = true;
            $examFee = $examFees->get($row->semester_id);
            if ($examFee) {
                $payments = $feePayments->get($row->id);
                $feePaid = $payments && $payments->where('exam_fee_id', $examFee->id)->isNotEmpty();
            }
            $row->fee_paid = $feePaid;
        });

        // Load waivers
        $waivers = ExamWaiver::with(['student.user', 'grantor'])->get()->keyBy('student_id');

        // Apply search if provided
        $search = $request->input('search');
        if ($search) {
            $defaulters = $defaulters->filter(function($s) use ($search) {
                return stripos($s->name, $search) !== false || stripos($s->enrollment_no, $search) !== false;
            })->values();
        }

        // Filter by program if provided
        $programs = \App\Models\Program::all();
        $programFilter = $request->input('program_id');
        if ($programFilter) {
            $defaulters = $defaulters->filter(fn($s) => (int)$s->program_id === (int)$programFilter)->values();
        }

        return view('exam.hall_tickets.index', compact('defaulters', 'waivers', 'programs', 'search', 'programFilter'));
    }

    public function generator(Request $request)
    {
        $user = Auth::user();
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';
        abort_unless($user->isAdmin() || $user->isCoe() || ($user->isHod() && $isExamDept), 403);

        $classSections = \App\Models\ClassSection::with(['program', 'semester'])->get();
        $selectedClassSectionId = $request->input('class_section_id');

        if (!$selectedClassSectionId && $classSections->isNotEmpty()) {
            $selectedClassSectionId = $classSections->first()->id;
        }

        $students = collect();
        $waivers = collect();

        if ($selectedClassSectionId) {
            $studentsList = Student::with('user')
                ->where('class_section_id', $selectedClassSectionId)
                ->where('status', 'active')
                ->get()
                ->sortBy('roll_no');

            $attendanceSummaries = AttendanceRecord::query()
                ->select([
                    'students.id',
                    DB::raw("sum(case when attendance_records.status = 'present' then 1 else 0 end) as present_count"),
                    DB::raw('count(*) as conducted_count'),
                ])
                ->join('students', 'students.id', '=', 'attendance_records.student_id')
                ->join('lecture_sessions', 'lecture_sessions.id', '=', 'attendance_records.lecture_session_id')
                ->where('students.class_section_id', $selectedClassSectionId)
                ->whereIn('lecture_sessions.status', ['conducted', 'locked'])
                ->groupBy('students.id')
                ->get()
                ->keyBy('id');

            $classSection = \App\Models\ClassSection::find($selectedClassSectionId);
            $examFee = $classSection ? \App\Models\ExamFee::where('semester_id', $classSection->semester_id)->first() : null;

            foreach ($studentsList as $student) {
                $summary = $attendanceSummaries->get($student->id);
                $conducted = $summary->conducted_count ?? 0;
                $present = $summary->present_count ?? 0;
                
                $student->percentage = $conducted > 0 ? round(($present / $conducted) * 100, 2) : 0;

                $feePaid = true;
                if ($examFee) {
                    $feePaid = \App\Models\ExamFeePayment::where('student_id', $student->id)
                        ->where('exam_fee_id', $examFee->id)
                        ->where('status', 'paid')
                        ->exists();
                }
                $student->fee_paid = $feePaid;

                $students->push($student);
            }

            $waivers = ExamWaiver::whereIn('student_id', $studentsList->pluck('id'))->get()->keyBy('student_id');
        }

        return view('exam.hall_tickets.generator', compact('classSections', 'selectedClassSectionId', 'students', 'waivers'));
    }

    public function storeWaiver(Request $request, Student $student)
    {
        $user = Auth::user();
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';
        abort_unless($user->isAdmin() || $user->isCoe() || ($user->isHod() && $isExamDept), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        ExamWaiver::updateOrCreate(
            ['student_id' => $student->id],
            [
                'granted_by' => $user->id,
                'reason' => $validated['reason'],
            ]
        );

        return back()->with('status', "Attendance waiver granted successfully for {$student->user->name}.");
    }

    public function destroyWaiver(Student $student)
    {
        $user = Auth::user();
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';
        abort_unless($user->isAdmin() || $user->isCoe() || ($user->isHod() && $isExamDept), 403);

        ExamWaiver::where('student_id', $student->id)->delete();

        return back()->with('status', "Attendance waiver revoked for {$student->user->name}.");
    }

    public function studentHallTicket()
    {
        $user = Auth::user();
        abort_unless($user->isStudent(), 403);

        $student = Student::with(['classSection', 'program', 'semester', 'examWaiver'])->where('user_id', $user->id)->firstOrFail();

        // Calculate student dynamic attendance percentage
        $attendanceSummary = AttendanceRecord::query()
            ->select([
                DB::raw("sum(case when attendance_records.status = 'present' then 1 else 0 end) as present_count"),
                DB::raw('count(*) as conducted_count'),
            ])
            ->join('lecture_sessions', 'lecture_sessions.id', '=', 'attendance_records.lecture_session_id')
            ->where('attendance_records.student_id', $student->id)
            ->whereIn('lecture_sessions.status', ['conducted', 'locked'])
            ->first();

        $conducted = $attendanceSummary->conducted_count ?? 0;
        $present = $attendanceSummary->present_count ?? 0;
        $percentage = $conducted > 0 ? round(($present / $conducted) * 100, 2) : 0;

        $hasWaiver = $student->examWaiver !== null;
        
        $examFee = \App\Models\ExamFee::where('semester_id', $student->semester_id)->first();
        $feePaid = true;
        $pendingFeeAmount = 0.00;
        if ($examFee) {
            $feePayment = \App\Models\ExamFeePayment::where('student_id', $student->id)
                ->where('exam_fee_id', $examFee->id)
                ->where('status', 'paid')
                ->first();
            $feePaid = $feePayment !== null;
            if (!$feePaid) {
                $pendingFeeAmount = $examFee->amount;
            }
        }

        $isEligible = ($percentage >= 75 || $hasWaiver) && $feePaid;

        $overallToAttend = max(0, (int) ceil(3 * $conducted - 4 * $present));
        $overallToSkip = max(0, (int) floor((4 * $present - 3 * $conducted) / 3));

        return view('student.hall_ticket.show', compact('student', 'percentage', 'isEligible', 'hasWaiver', 'overallToAttend', 'overallToSkip', 'feePaid', 'pendingFeeAmount'));
    }

    public function downloadHallTicket()
    {
        $user = Auth::user();
        $student = null;

        if ($user->isStudent()) {
            $student = Student::with(['classSection', 'program', 'semester', 'examWaiver'])->where('user_id', $user->id)->firstOrFail();
        } else {
            $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';
            abort_unless($user->isAdmin() || $isExamDept || $user->isHod(), 403);

            // HODs/Admins can print for any student
            $studentId = request('student_id');
            if ($studentId) {
                $student = Student::with(['classSection', 'program', 'semester', 'examWaiver'])->findOrFail($studentId);
                
                // If it is HOD and not exam department, they can only print for their own department's students
                if ($user->isHod() && !$isExamDept) {
                    abort_unless((int)$student->program->department_id === (int)$user->facultyProfile->department_id, 403);
                }
            }
        }

        if (!$student) {
            abort(400, 'Student details not specified.');
        }

        // Verify clearance
        $attendanceSummary = AttendanceRecord::query()
            ->select([
                DB::raw("sum(case when attendance_records.status = 'present' then 1 else 0 end) as present_count"),
                DB::raw('count(*) as conducted_count'),
            ])
            ->join('lecture_sessions', 'lecture_sessions.id', '=', 'attendance_records.lecture_session_id')
            ->where('attendance_records.student_id', $student->id)
            ->whereIn('lecture_sessions.status', ['conducted', 'locked'])
            ->first();

        $conducted = $attendanceSummary->conducted_count ?? 0;
        $present = $attendanceSummary->present_count ?? 0;
        $percentage = $conducted > 0 ? round(($present / $conducted) * 100, 2) : 0;

        $hasWaiver = $student->examWaiver !== null;
        
        $examFee = \App\Models\ExamFee::where('semester_id', $student->semester_id)->first();
        $feePaid = true;
        if ($examFee) {
            $feePaid = \App\Models\ExamFeePayment::where('student_id', $student->id)
                ->where('exam_fee_id', $examFee->id)
                ->where('status', 'paid')
                ->exists();
        }

        $isEligible = ($percentage >= 75 || $hasWaiver) && $feePaid;

        // If not eligible, block download
        if (!$isEligible && !$user->isAdmin() && !($user->isHod() && $user->facultyProfile?->department?->department_code === 'EXAM')) {
            abort(403, 'Your End-Semester Hall Ticket is locked due to low attendance or unpaid exam fees.');
        }

        // Get registered subjects for the student's class section
        $subjects = \App\Models\SubjectAssignment::with('subject')
            ->where('class_section_id', $student->class_section_id)
            ->where('status', 'active')
            ->get();

        // Printable admit card view
        return view('student.hall_ticket.pdf', compact('student', 'percentage', 'subjects', 'hasWaiver'));
    }
}
