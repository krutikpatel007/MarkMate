@extends('layouts.app')

@section('title', 'Faculty Profile | SCSA Attendance')
@section('page-title')
    <a href="{{ route('hr.dashboard') }}" style="text-decoration: none; color: var(--color-scsa-muted); font-size: 0.875rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.25rem; margin-bottom: 0.5rem;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 0.875rem; height: 0.875rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg> Back to HR Directory
    </a>
    <br>{{ $faculty->user->name }}
@endsection
@section('page-subtitle', "Employee Code: {$faculty->employee_code} | Department: {$faculty->department->department_code} | Designation: " . ($faculty->designation ?? 'Faculty'))

@section('content')
    <!-- Status & Error Banners -->
    @if(session('status'))
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
            {{ session('status') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-2" style="gap: 1.5rem; align-items: flex-start; margin-bottom: 2rem;">
        <!-- Left: Class Allocation & Timetable Load -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <section class="card">
                <h2>Teaching Load Breakdown</h2>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <span class="muted">Weekly Teaching Load Hours:</span>
                    <span class="badge {{ $faculty->weekly_load > 20 ? 'danger' : 'success' }}" style="font-size: 1rem; padding: 0.5rem 1rem;">
                        {{ $faculty->weekly_load }} hours / week
                    </span>
                </div>
                
                <h3>Active Subject Assignments</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Load</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->classSection->display_name }}</td>
                                    <td>
                                        <strong>{{ $assignment->subject->subject_name }}</strong>
                                        <div class="muted">{{ $assignment->subject->subject_code }}</div>
                                    </td>
                                    <td>
                                        @php
                                            $slotCount = \App\Models\Timetable::where('subject_assignment_id', $assignment->id)->where('status', 'active')->count();
                                        @endphp
                                        <span class="badge secondary">{{ $slotCount }} lectures/week</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="muted" style="text-align: center;">No active subject assignments.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Salary Configuration Form -->
            <section class="card">
                <h2>Salary Configuration</h2>
                <form method="post" action="{{ route('hr.faculty.salary.store', $faculty->id) }}">
                    @csrf
                    <div class="grid grid-2" style="gap: 1rem; margin-bottom: 1rem;">
                        <div class="field">
                            <label for="basic_pay">Basic Pay (₹)</label>
                            <input type="number" step="0.01" id="basic_pay" name="basic_pay" value="{{ $faculty->salaryConfig?->basic_pay ?? '0.00' }}" required>
                        </div>
                        <div class="field">
                            <label for="hra">HRA (₹)</label>
                            <input type="number" step="0.01" id="hra" name="hra" value="{{ $faculty->salaryConfig?->hra ?? '0.00' }}" required>
                        </div>
                        <div class="field">
                            <label for="da">Dearness Allowance (₹)</label>
                            <input type="number" step="0.01" id="da" name="da" value="{{ $faculty->salaryConfig?->da ?? '0.00' }}" required>
                        </div>
                        <div class="field">
                            <label for="special_allowance">Special Allowance (₹)</label>
                            <input type="number" step="0.01" id="special_allowance" name="special_allowance" value="{{ $faculty->salaryConfig?->special_allowance ?? '0.00' }}" required>
                        </div>
                        <div class="field" style="grid-column: span 2;">
                            <label for="deductions">Deductions / PF / Tax (₹)</label>
                            <input type="number" step="0.01" id="deductions" name="deductions" value="{{ $faculty->salaryConfig?->deductions ?? '0.00' }}" required>
                        </div>
                    </div>
                    <button class="button" type="submit">Update Salary Settings</button>
                </form>
            </section>
        </div>

        <!-- Right: Payslip Generator & Performance Appraisals -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Payslip Generator Card -->
            <section class="card">
                <h2>Payslip Management</h2>
                @if($faculty->salaryConfig)
                    <form method="post" action="{{ route('hr.faculty.payslip.store', $faculty->id) }}" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: flex-end;">
                        @csrf
                        <div class="field" style="margin-bottom: 0; flex: 1;">
                            <label for="month">Month</label>
                            <select id="month" name="month" required>
                                @for($m=1; $m<=12; $m++)
                                    <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 10)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="field" style="margin-bottom: 0; flex: 1;">
                            <label for="year">Year</label>
                            <select id="year" name="year" required>
                                @for($y=2026; $y<=2028; $y++)
                                    <option value="{{ $y }}" {{ date('Y') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <button class="button" type="submit" style="min-height: unset; padding: 0.65rem 1rem;">Generate Payslip</button>
                    </form>
                @else
                    <div style="background-color: var(--color-scsa-line); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;" class="muted">
                        Configure salary settings on the left to enable payslip generation.
                    </div>
                @endif

                <h3>Payslip History</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Month/Year</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($faculty->payslips as $payslip)
                                <tr>
                                    <td>{{ date('F', mktime(0,0,0, $payslip->month, 10)) }} {{ $payslip->year }}</td>
                                    <td><strong>₹{{ number_format($payslip->net_salary, 2) }}</strong></td>
                                    <td><span class="badge success">{{ ucfirst($payslip->status) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="muted" style="text-align: center;">No payslips generated yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Appraisals Section -->
            <section class="card">
                <h2>Performance Appraisals</h2>
                <form method="post" action="{{ route('hr.faculty.appraisal.store', $faculty->id) }}" style="margin-bottom: 1.5rem;">
                    @csrf
                    <div class="grid grid-2" style="gap: 1rem; margin-bottom: 1rem;">
                        <div class="field">
                            <label for="academic_year">Academic Year</label>
                            <input type="text" id="academic_year" name="academic_year" placeholder="2026-27" required>
                        </div>
                        <div class="field">
                            <label for="overall_rating">Overall Rating (1.0 - 5.0)</label>
                            <input type="number" step="0.1" min="1" max="5" id="overall_rating" name="overall_rating" placeholder="4.5" required>
                        </div>
                        <div class="field">
                            <label for="score_teaching">Teaching Performance Score (0-100)</label>
                            <input type="number" min="0" max="100" id="score_teaching" name="score_teaching" required>
                        </div>
                        <div class="field">
                            <label for="score_research">Research Contribution Score (0-100)</label>
                            <input type="number" min="0" max="100" id="score_research" name="score_research" required>
                        </div>
                        <div class="field" style="grid-column: span 2;">
                            <label for="score_administrative">Administrative Score (0-100)</label>
                            <input type="number" min="0" max="100" id="score_administrative" name="score_administrative" required>
                        </div>
                        <div class="field" style="grid-column: span 2;">
                            <label for="review_comments">Review Comments</label>
                            <textarea id="review_comments" name="review_comments" rows="3" style="width: 100%; border: 1px solid var(--color-scsa-line); border-radius: 6px; padding: 0.5rem;"></textarea>
                        </div>
                    </div>
                    <button class="button" type="submit">Submit Appraisal Record</button>
                </form>

                <h3>Appraisal Registry</h3>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @forelse($faculty->appraisals as $appraisal)
                        <div style="border: 1px solid var(--color-scsa-line); border-radius: 8px; padding: 1rem; font-size: 0.9rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <strong>Year: {{ $appraisal->academic_year }}</strong>
                                <span class="badge" style="background-color: var(--color-scsa-accent); color: white; font-weight: 700;">
                                    ★ {{ number_format($appraisal->overall_rating, 1) }} / 5.0
                                </span>
                            </div>
                            <div class="muted" style="font-size: 0.8rem; margin-bottom: 0.5rem;">
                                Reviewed by: {{ $appraisal->reviewer->name }} on {{ $appraisal->created_at->format('d M Y') }}
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; background: var(--color-scsa-line); padding: 0.5rem; border-radius: 6px; margin-bottom: 0.5rem; text-align: center; font-size: 0.8rem;">
                                <div><strong>Teaching:</strong> {{ $appraisal->score_teaching }}%</div>
                                <div><strong>Research:</strong> {{ $appraisal->score_research }}%</div>
                                <div><strong>Admin:</strong> {{ $appraisal->score_administrative }}%</div>
                            </div>
                            @if($appraisal->review_comments)
                                <div class="muted" style="font-style: italic; font-size: 0.85rem;">
                                    "{{ $appraisal->review_comments }}"
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="muted" style="text-align: center;">No appraisals filed yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
