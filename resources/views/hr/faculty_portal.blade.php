@extends('layouts.app')

@section('title', 'My HR Portal | SCSA Attendance')
@section('page-title', 'My HR Portal')
@section('page-subtitle', 'Track your weekly load metrics, leave applications, and performance reviews.')

@section('content')
    <!-- Status & Error Banners -->
    @if(session('status'))
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
            {{ session('status') }}
        </div>
    @endif

    <!-- Profile Overview Grid -->
    <div class="grid grid-3" style="margin-bottom: 2rem;">
        <div class="card stat-card" style="border-left: 4px solid var(--color-scsa-accent); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">My Weekly Load</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-accent);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat" style="font-size: 2.25rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.5rem;">{{ $faculty->weekly_load }} hrs</div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Target Load limit: 20 hours</div>
        </div>

        <div class="card stat-card" style="border-left: 4px solid #10b981; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">My Rating</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: #10b981;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499c.176-.381.772-.381.948 0l2.203 4.5 4.978.723c.42.061.587.575.283.868l-3.6 3.5 1.213 4.954c.08.327-.264.577-.552.41L12 16.347l-4.417 2.385c-.288.167-.632-.083-.552-.41l1.213-4.954-3.6-3.5c-.304-.293-.137-.807.283-.868l4.978-.723 2.203-4.5z" />
                </svg>
            </div>
            <div class="stat" style="font-size: 2.25rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.5rem;">
                {{ $faculty->avg_feedback ?: 'N/A' }}
            </div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Cumulative student reviews</div>
        </div>

        <div class="card stat-card" style="border-left: 4px solid #4f46e5; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Designation</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: #4f46e5;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                </svg>
            </div>
            <div class="stat" style="font-size: 1.8rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ $faculty->designation ?? 'Lecturer' }}
            </div>
            <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Employee Code: {{ $faculty->employee_code }}</div>
        </div>
    </div>

    <!-- Main Content Tabs Area -->
    <div class="grid grid-2" style="gap: 1.5rem; align-items: flex-start;">
        
        <!-- Left Tab Card: Assignments -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Active Courses -->
            <section class="card">
                <h2>My Active Assignments</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->classSection->display_name }}</td>
                                    <td><span class="badge secondary">{{ $assignment->subject->subject_code }}</span></td>
                                    <td><strong>{{ $assignment->subject->subject_name }}</strong></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="muted" style="text-align: center;">No subject assignments allocated.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Right Tab Card: Leaves & Appraisals -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Leave Management -->
            <section class="card">
                <h2>Leaves Registry & Application</h2>
                
                <!-- Quick Apply Leave Redirect -->
                <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; background-color: var(--color-scsa-line); padding: 1rem; border-radius: 8px;">
                    <div>
                        <strong>Need to apply for a leave?</strong>
                        <div class="muted" style="font-size: 0.8rem;">Submit your dates and document attachments.</div>
                    </div>
                    <a href="{{ route('leaves.faculty.index') }}" class="button" style="text-decoration: none; min-height: unset; padding: 0.5rem 1rem;">Apply Leave</a>
                </div>

                <h3>My Leave History</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Dates</th>
                                <th>Type</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaves as $leave)
                                <tr>
                                    <td>
                                        <strong>{{ $leave->start_date->format('d M') }} - {{ $leave->end_date->format('d M') }}</strong>
                                        <div class="muted" style="font-size: 0.7rem;">{{ $leave->created_at->format('d M Y') }}</div>
                                    </td>
                                    <td><span class="badge secondary">{{ ucfirst($leave->leave_type ?? 'casual') }}</span></td>
                                    <td><div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $leave->reason }}">{{ $leave->reason }}</div></td>
                                    <td>
                                        @if($leave->status === 'approved')
                                            <span class="badge success">Approved</span>
                                        @elseif($leave->status === 'rejected')
                                            <span class="badge danger">Rejected</span>
                                        @else
                                            <span class="badge" style="background-color: #fef3c7; color: #d97706;">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="muted" style="text-align: center;">No leaves requested.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Appraisals -->
            <section class="card">
                <h2>My Appraisals</h2>
                @forelse($faculty->appraisals as $appraisal)
                    <div style="border: 1px solid var(--color-scsa-line); border-radius: 8px; padding: 1rem; margin-bottom: 1rem; font-size: 0.9rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong>Academic Year: {{ $appraisal->academic_year }}</strong>
                            <span class="badge" style="background-color: var(--color-scsa-accent); color: white; font-weight: 700;">
                                ★ {{ number_format($appraisal->overall_rating, 1) }} / 5.0
                            </span>
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
            </section>
        </div>
    </div>
@endsection
