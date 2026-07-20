<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesAcademicManagement;
use App\Models\Faculty;
use App\Models\FacultySalaryConfig;
use App\Models\FacultyPayslip;
use App\Models\FacultyAppraisal;
use App\Models\FacultyLeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FacultyHrController extends Controller
{
    use AuthorizesAcademicManagement;

    public function adminDashboard(): View
    {
        $this->ensureAcademicManager();

        $faculties = Faculty::with(['user', 'salaryConfig', 'feedbacks', 'appraisals'])
            ->whereIn('department_id', $this->manageableDepartmentIds())
            ->get()
            ->map(function ($f) {
                $f->weekly_load = $f->weeklyLoadHours();
                $f->avg_feedback = round($f->feedbacks->avg('rating'), 2) ?: null;
                return $f;
            });

        // Pending leaves for manageable departments
        $pendingLeaves = FacultyLeaveRequest::with(['faculty.user', 'faculty.department'])
            ->where('status', 'pending')
            ->whereHas('faculty', function ($q) {
                $q->whereIn('department_id', $this->manageableDepartmentIds());
            })
            ->latest()
            ->get();

        return view('hr.index', [
            'faculties' => $faculties,
            'pendingLeaves' => $pendingLeaves,
        ]);
    }

    public function showFacultyHr(Faculty $faculty): View
    {
        $this->ensureAcademicManager();
        $this->authorizeProgram($faculty->department->programs()->first() ?? new \App\Models\Program());

        $faculty->load(['user', 'salaryConfig', 'payslips', 'appraisals.reviewer']);
        $faculty->weekly_load = $faculty->weeklyLoadHours();
        $faculty->avg_feedback = round($faculty->feedbacks->avg('rating'), 2) ?: null;

        // Retrieve class/subject assignment breakdown
        $assignments = $faculty->subjectAssignments()
            ->with(['classSection', 'subject'])
            ->where('status', 'active')
            ->get();

        return view('hr.show', [
            'faculty' => $faculty,
            'assignments' => $assignments,
        ]);
    }

    public function storeSalaryConfig(Request $request, Faculty $faculty)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isHr(), 403);
        
        $validated = $request->validate([
            'basic_pay' => ['required', 'numeric', 'min:0'],
            'hra' => ['required', 'numeric', 'min:0'],
            'da' => ['required', 'numeric', 'min:0'],
            'special_allowance' => ['required', 'numeric', 'min:0'],
            'deductions' => ['required', 'numeric', 'min:0'],
        ]);

        $faculty->salaryConfig()->updateOrCreate(
            ['faculty_id' => $faculty->id],
            $validated
        );

        return redirect()->back()->with('status', 'Salary configuration updated successfully.');
    }

    public function generatePayslip(Request $request, Faculty $faculty)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isHr(), 403);

        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2030'],
        ]);

        $config = $faculty->salaryConfig;
        if (!$config) {
            return redirect()->back()->withErrors(['error' => 'Please configure salary details first.']);
        }

        // Prevent duplicate payslips for the same month/year
        $exists = FacultyPayslip::where('faculty_id', $faculty->id)
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['error' => 'Payslip for this month and year has already been generated.']);
        }

        $netSalary = $config->basic_pay + $config->hra + $config->da + $config->special_allowance - $config->deductions;

        FacultyPayslip::create([
            'faculty_id' => $faculty->id,
            'month' => $validated['month'],
            'year' => $validated['year'],
            'basic_pay' => $config->basic_pay,
            'hra' => $config->hra,
            'da' => $config->da,
            'special_allowance' => $config->special_allowance,
            'deductions' => $config->deductions,
            'net_salary' => max(0, $netSalary),
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return redirect()->back()->with('status', 'Payslip generated and marked as paid successfully.');
    }

    public function submitAppraisal(Request $request, Faculty $faculty)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isHr(), 403);

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'score_teaching' => ['required', 'integer', 'min:0', 'max:100'],
            'score_research' => ['required', 'integer', 'min:0', 'max:100'],
            'score_administrative' => ['required', 'integer', 'min:0', 'max:100'],
            'overall_rating' => ['required', 'numeric', 'min:1.0', 'max:5.0'],
            'review_comments' => ['nullable', 'string', 'max:1000'],
        ]);

        FacultyAppraisal::create(array_merge($validated, [
            'faculty_id' => $faculty->id,
            'reviewer_id' => Auth::id(),
        ]));

        return redirect()->back()->with('status', 'Performance appraisal submitted successfully.');
    }

    public function myHrPortal(): View
    {
        abort_unless(Auth::user()->isFaculty(), 403);

        $faculty = Auth::user()->facultyProfile;
        $faculty->load(['appraisals.reviewer']);
        
        $faculty->weekly_load = $faculty->weeklyLoadHours();
        $faculty->avg_feedback = round($faculty->feedbacks->avg('rating'), 2) ?: null;

        $leaves = FacultyLeaveRequest::where('faculty_id', $faculty->id)
            ->latest()
            ->get();

        $assignments = $faculty->subjectAssignments()
            ->with(['classSection', 'subject'])
            ->where('status', 'active')
            ->get();

        return view('hr.faculty_portal', [
            'faculty' => $faculty,
            'leaves' => $leaves,
            'assignments' => $assignments,
        ]);
    }
}
