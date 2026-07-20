@extends('layouts.app')

@section('title', 'HR & Faculty Management | SCSA Attendance')
@section('page-title', 'HR & Faculty Management')
@section('page-subtitle', 'Overview of faculty load calculations, salary profiles, appraisals, and leave approvals.')

@section('content')
    <!-- Status Banner -->
    @if(session('status'))
        <div class="alert alert-success" style="margin-bottom: 1.5rem; animation: fadeIn 0.4s ease-in-out;">
            {{ session('status') }}
        </div>
    @endif

    <!-- Metric Cards -->
    <div class="grid grid-3" style="margin-bottom: 2rem;">
        <div class="card stat-card" style="border-left: 4px solid var(--color-scsa-accent); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Total Staff</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-accent);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>
            <div class="stat" style="font-size: 2.25rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.5rem;">{{ $faculties->count() }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Active Faculty Members</div>
        </div>

        <div class="card stat-card" style="border-left: 4px solid #f59e0b; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Pending Leaves</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: #f59e0b;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18V6a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 6v3.75m-18 0A2.25 2.25 0 005.25 12h13.5A2.25 2.25 0 0021 9.75V6" />
                </svg>
            </div>
            <div class="stat" style="font-size: 2.25rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.5rem;">{{ $pendingLeaves->count() }}</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Awaiting Approvals</div>
        </div>

        <div class="card stat-card" style="border-left: 4px solid #10b981; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Average University Feedback</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: #10b981;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499c.176-.381.772-.381.948 0l2.203 4.5 4.978.723c.42.061.587.575.283.868l-3.6 3.5 1.213 4.954c.08.327-.264.577-.552.41L12 16.347l-4.417 2.385c-.288.167-.632-.083-.552-.41l1.213-4.954-3.6-3.5c-.304-.293-.137-.807.283-.868l4.978-.723 2.203-4.5z" />
                </svg>
            </div>
            <div class="stat" style="font-size: 2.25rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.5rem;">
                {{ number_format($faculties->avg('avg_feedback') ?? 0.0, 1) }}
            </div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Out of 5.0 rating</div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-3" style="gap: 1.5rem; align-items: flex-start;">
        <!-- Left 2 Columns: Faculty Directory -->
        <section class="card" style="grid-column: span 2;">
            <h2>Faculty Directory</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Designation</th>
                            <th>Weekly Load</th>
                            <th>Feedback</th>
                            <th>Salary Config</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($faculties as $f)
                            <tr>
                                <td>
                                    <strong>{{ $f->user->name }}</strong>
                                    <div class="muted">{{ $f->employee_code }} | {{ $f->department->department_code }}</div>
                                </td>
                                <td>{{ $f->designation ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge {{ $f->weekly_load > 20 ? 'danger' : 'success' }}">
                                        {{ $f->weekly_load }} hrs/week
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: var(--color-scsa-secondary); color: var(--color-scsa-ink); font-weight: 700;">
                                        ★ {{ number_format($f->avg_feedback ?? 0.0, 1) }}
                                    </span>
                                </td>
                                <td>
                                    @if($f->salaryConfig)
                                        <span class="badge success">Configured</span>
                                    @else
                                        <span class="badge danger">Not Set</span>
                                    @endif
                                </td>
                                <td>
                                    <a class="button secondary" href="{{ route('hr.faculty.show', $f->id) }}" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; text-decoration: none;">Profile</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="muted" style="text-align: center;">No faculty members registered.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Right 1 Column: Leave Requests -->
        <section class="card">
            <h2>Pending Leaves</h2>
            @forelse($pendingLeaves as $leave)
                <div style="border: 1px solid var(--color-scsa-line); border-radius: 8px; padding: 1rem; margin-bottom: 1rem; position: relative;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <strong>{{ $leave->faculty->user->name }}</strong>
                            <div class="muted" style="font-size: 0.75rem;">{{ $leave->faculty->department->department_code }} | {{ ucfirst($leave->leave_type) }}</div>
                        </div>
                        <span class="badge" style="background: #fef3c7; color: #d97706;">Pending</span>
                    </div>
                    <div style="margin-top: 0.5rem; font-size: 0.85rem;">
                        <strong>Dates:</strong> {{ $leave->start_date->format('d M') }} to {{ $leave->end_date->format('d M') }}
                    </div>
                    <div class="muted" style="font-size: 0.8rem; margin-top: 0.25rem;">
                        "{{ $leave->reason }}"
                    </div>
                    
                    <!-- Quick Decider form -->
                    <form method="post" action="{{ route('leaves.faculty.hod.decide', $leave->id) }}" style="margin-top: 0.75rem; display: flex; gap: 0.5rem;">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" id="status_{{ $leave->id }}" value="approved">
                        <button type="submit" class="button" onclick="document.getElementById('status_{{ $leave->id }}').value='approved'" style="flex: 1; padding: 0.4rem; font-size: 0.75rem; min-height: unset; background-color: #10b981;">Approve</button>
                        <button type="submit" class="button secondary" onclick="document.getElementById('status_{{ $leave->id }}').value='rejected'" style="flex: 1; padding: 0.4rem; font-size: 0.75rem; min-height: unset; border-color: #ef4444; color: #ef4444;">Reject</button>
                    </form>
                </div>
            @empty
                <p class="muted" style="text-align: center; margin-top: 1rem;">No leave approvals pending.</p>
            @endforelse
        </section>
    </div>
@endsection
