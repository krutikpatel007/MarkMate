@extends('layouts.app')

@section('title', 'Dashboard | SCSA Attendance')
@section('page-title', 'Dashboard')
@section('page-subtitle')
    @if(auth()->user()->isStudent())
        {{ $student->classSection->display_name }} | Enrollment {{ $student->enrollment_no }}
    @elseif(isset($isExamDept) && $isExamDept)
        Examination evaluation and internal marks verification overview
    @elseif(auth()->user()->isFaculty())
        Assigned lectures and approval updates
    @else
        SCSA attendance overview
    @endif
@endsection

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @if(isset($isExamDept) && $isExamDept)
        <div class="grid grid-4">
            <div class="card stat-card" style="border-left: 4px solid var(--color-scsa-accent); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Total Assigned Courses</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-accent); opacity: 0.8;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <div class="stat" style="font-size: 2.25rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.5rem;">{{ $stats['total_courses'] }}</div>
                <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Active academic courses</div>
            </div>

            <div class="card stat-card" style="border-left: 4px solid var(--color-scsa-gold); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Draft Mode (Faculty)</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-gold); opacity: 0.8;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.83 17.59a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                </div>
                <div class="stat" style="font-size: 2.25rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.5rem;">{{ $stats['draft_count'] }}</div>
                <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Marks entry in progress</div>
            </div>

            <div class="card stat-card" style="border-left: 4px solid #3b82f6; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">HOD Review Pending</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: #3b82f6; opacity: 0.8;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
                <div class="stat" style="font-size: 2.25rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.5rem;">{{ $stats['hod_review_count'] }}</div>
                <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Submitted to academic HODs</div>
            </div>

            <div class="card stat-card" style="border-left: 4px solid var(--color-scsa-success); padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="muted" style="font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Finalized (Exam Dept)</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--color-scsa-success); opacity: 0.8;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat" style="font-size: 2.25rem; font-weight: 800; color: var(--color-scsa-ink); margin-top: 0.5rem;">{{ $stats['submitted_to_exam_count'] }}</div>
                <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Locked & sent to Exam Controller</div>
            </div>
        </div>

        <div class="grid grid-3" style="margin-top: 1rem;">
            <section class="card" style="grid-column: span 2;">
                <div class="actions" style="justify-content: space-between; margin-bottom: 0.75rem;">
                    <h2 style="margin-bottom: 0;">Recently Finalized Marks Sheets</h2>
                    <a class="button" href="{{ route('marks.index') }}">Browse All Marks Sheets</a>
                </div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                        <tr>
                            <th>Subject / Course</th>
                            <th>Program &amp; Section</th>
                            <th>Evaluated By</th>
                            <th>Received At</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($recentMarksSheets as $sheet)
                            <tr>
                                <td>
                                    <strong>{{ $sheet->subject->subject_name }}</strong>
                                    <div class="muted">{{ $sheet->subject->subject_code }}</div>
                                </td>
                                <td>
                                    {{ $sheet->classSection->program->program_name }}
                                    <div class="muted">{{ $sheet->classSection->display_name }}</div>
                                </td>
                                <td>{{ $sheet->faculty->user->name }}</td>
                                <td>
                                    <div style="font-weight: 500;">
                                        {{ $sheet->submitted_to_exam_at ? \Carbon\Carbon::parse($sheet->submitted_to_exam_at)->format('d M Y') : '-' }}
                                    </div>
                                    <div class="muted" style="font-size: 0.75rem;">
                                        {{ $sheet->submitted_to_exam_at ? \Carbon\Carbon::parse($sheet->submitted_to_exam_at)->format('h:i A') : '' }}
                                    </div>
                                </td>
                                <td>
                                    <a class="button secondary" href="{{ route('marks.show', $sheet) }}">View &amp; Review</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="muted" style="text-align: center; padding: 2rem 0;">
                                    No finalized internal marks sheets received in the Exam Department yet.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card" style="grid-row: span 2;">
                <h2>Official Academic Notice Board</h2>
                @forelse($notifications as $notification)
                    @php
                        $isUrgent = Str::contains(strtolower($notification->title), ['defaulter', 'low', 'warning', 'urgent', 'rejected', 'marks']);
                        $stamp = $isUrgent ? 'URGENT ALERT' : 'OFFICIAL NOTICE';
                        $noticeColor = $isUrgent ? 'var(--color-scsa-danger)' : 'var(--color-scsa-gold)';
                        $pinnedClass = $isUrgent ? 'notice-pinned' : '';
                    @endphp
                    <div class="list-divider {{ $pinnedClass }}" style="padding: 0.75rem 0.5rem; border-radius: 0.375rem; margin-bottom: 0.5rem; transition: background 0.1s ease;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                            <span class="university-stamp" style="font-size: 0.625rem; border-color: {{ $noticeColor }}; color: {{ $noticeColor }};">{{ $stamp }}</span>
                            <div class="muted" style="font-size: 0.75rem;">{{ $notification->created_at->format('d M Y') }}</div>
                        </div>
                        <strong style="font-size: 0.875rem; color: var(--color-scsa-ink); display: block;">{{ $notification->title }}</strong>
                        <div class="muted" style="margin-top: 0.25rem; line-height: 1.4; font-size: 0.8125rem;">{{ $notification->message }}</div>
                    </div>
                @empty
                    <p class="muted" style="text-align: center; padding: 2rem 0;">No official notices or academic alerts posted at this time.</p>
                @endforelse
            </section>

            <!-- Results Release Controls -->
            <section class="card" style="grid-column: span 2; margin-top: 1rem;">
                <h2 style="margin-bottom: 0.5rem;">Centralized Results Release Control</h2>
                <p class="muted" style="font-size: 0.85rem; margin-bottom: 1.25rem; line-height: 1.45;">
                    Toggle result release status for each class section. When results are released, students can view their final Semester Grade Card (provisional transcript). Otherwise, results remain locked and hidden from student profiles.
                </p>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                        <tr>
                            <th>Class Section</th>
                            <th>Program</th>
                            <th>Semester</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($classSections as $section)
                            <tr>
                                <td>
                                    <strong>{{ $section->display_name }}</strong>
                                </td>
                                <td>{{ $section->program->program_name }}</td>
                                <td>Semester {{ $section->semester->semester_no }}</td>
                                <td>
                                    <span class="badge {{ $section->results_released ? 'success' : 'danger' }}"" style="padding: 0.35rem 0.6rem; font-weight: 700; font-size: 0.75rem;">
                                        {{ $section->results_released ? '🔓 Released to Students' : '🔒 Locked / Hidden' }}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <form method="post" action="{{ route('exam.classes.toggle-results', $section) }}" style="display: inline;">
                                        @csrf
                                        <button class="button {{ $section->results_released ? 'secondary' : '' }}" type="submit" style="font-size: 0.75rem; padding: 0.4rem 0.875rem; min-height: unset; border-radius: var(--border-radius-md);">
                                            {{ $section->results_released ? 'Lock Results' : 'Release Results' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="muted" style="text-align: center; padding: 2rem 0;">
                                    No active class sections found.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @elseif(auth()->user()->isAdmin() || auth()->user()->isHod())
        <div class="grid grid-4">
            <a class="card stat-card" href="{{ route('attendance.monitor') }}" style="text-decoration: none;"><div class="muted">Total Lectures Today</div><div class="stat">{{ $stats['sessions_today'] }}</div></a>
            <a class="card stat-card" href="{{ route('attendance.monitor', ['status' => 'conducted']) }}" style="text-decoration: none;"><div class="muted">Submitted</div><div class="stat">{{ $stats['submitted_today'] }}</div></a>
            <a class="card stat-card" href="{{ route('attendance.monitor', ['status' => 'pending']) }}" style="text-decoration: none;"><div class="muted">Pending</div><div class="stat">{{ $stats['pending_sessions'] }}</div></a>
            <a class="card stat-card" href="{{ route('extra-lectures.index') }}" style="text-decoration: none;"><div class="muted">Pending Extra Requests</div><div class="stat">{{ $stats['pending_extra_requests'] }}</div></a>
            <a class="card stat-card" href="{{ route('reports.index') }}#low-attendance-classes" style="text-decoration: none;"><div class="muted">Classes Below 75%</div><div class="stat">{{ $stats['low_attendance_classes'] }}</div></a>
            <a class="card stat-card" href="{{ route('attendance.monitor', ['status_group' => 'pending']) }}" style="text-decoration: none;"><div class="muted">Faculty Pending Submission</div><div class="stat">{{ $stats['faculty_pending'] }}</div></a>
            <a class="card stat-card" href="{{ route('reports.index') }}#defaulters" style="text-decoration: none;"><div class="muted">Defaulters Below 75%</div><div class="stat">{{ $stats['defaulters'] }}</div></a>
            <a class="card stat-card" href="{{ route('attendance.monitor', ['status' => 'cancelled']) }}" style="text-decoration: none;"><div class="muted">Cancelled Today</div><div class="stat">{{ $stats['cancelled_today'] }}</div></a>
        </div>

        <div class="grid grid-3" style="margin-top: 1rem;">
            <div class="card" style="padding: 1.25rem;">
                <h3 style="font-size: 0.9375rem; margin-bottom: 0.75rem; text-align: center; color: var(--color-scsa-accent);">Daily Attendance Averages</h3>
                <div style="height: 160px; position: relative;">
                    <canvas id="dailyAveragesChart"></canvas>
                </div>
            </div>
            <div class="card" style="padding: 1.25rem;">
                <h3 style="font-size: 0.9375rem; margin-bottom: 0.75rem; text-align: center; color: var(--color-scsa-accent);">Subject Attendance Averages</h3>
                <div style="height: 160px; position: relative;">
                    <canvas id="subjectAveragesChart"></canvas>
                </div>
            </div>
            <div class="card" style="padding: 1.25rem;">
                <h3 style="font-size: 0.9375rem; margin-bottom: 0.75rem; text-align: center; color: var(--color-scsa-accent);">Monthly Attendance Trends</h3>
                <div style="height: 160px; position: relative;">
                    <canvas id="monthlyAveragesChart"></canvas>
                </div>
            </div>
        </div>

        <section class="card" style="margin-top: 1rem;">
            <div class="actions" style="justify-content: space-between; margin-bottom: 0.75rem;">
                <h2 style="margin-bottom: 0;">Today's Lectures</h2>
                <a class="button secondary" href="{{ route('attendance.monitor') }}">Open Attendance Monitor</a>
            </div>
            <table>
                <thead>
                <tr>
                    <th>Time</th>
                    <th>Subject and Class</th>
                    <th>Faculty</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($todaySessionsAll as $session)
                    <tr>
                        <td>{{ $session->start_time ? substr($session->start_time, 0, 5).' - '.substr($session->end_time, 0, 5) : '-' }}</td>
                        <td>
                            {{ $session->subjectAssignment->subject->subject_name }}
                            <div class="muted">{{ $session->subjectAssignment->classSection->display_name }}{{ $session->lecture_no ? ' | Lecture '.$session->lecture_no : '' }}</div>
                        </td>
                        <td>{{ $session->subjectAssignment->faculty->user->name }}</td>
                        <td><span class="badge">{{ $session->status }}</span></td>
                        <td><a class="button secondary" href="{{ route('attendance.show', $session) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No lecture sessions scheduled for today.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <div class="grid grid-2" style="margin-top: 1rem;">
            <section class="card">
                <h2>Faculty Who Have Not Submitted Attendance</h2>
                @forelse($facultyPending as $item)
                    <div class="list-divider">
                        <strong>{{ $item['faculty'] }}</strong>
                        <div class="muted">{{ $item['count'] }} pending lecture(s): {{ $item['subjects']->join(', ') }}</div>
                    </div>
                @empty
                    <p class="muted">All today's lectures are submitted or cancelled.</p>
                @endforelse
            </section>

            <section class="card">
                <h2 id="low-attendance-classes">Classes With Low Attendance</h2>
                @forelse($lowAttendanceClasses as $class)
                    <div class="list-divider">
                        <strong>{{ $class->display_name }}</strong>
                        <div class="muted">{{ $class->present_count }} present out of {{ $class->conducted_count }} conducted entries</div>
                        <span class="badge danger">{{ $class->percentage }}%</span>
                    </div>
                @empty
                    <p class="muted">No class is currently below 75%.</p>
                @endforelse
            </section>

            <section class="card">
                <div class="actions" style="justify-content: space-between; margin-bottom: 0.75rem;">
                    <h2 style="margin-bottom: 0;">Late Submissions</h2>
                    <a class="button secondary" href="{{ route('attendance.monitor', ['view' => 'late']) }}">View All</a>
                </div>
                @forelse($lateSubmissions as $session)
                    <div class="list-divider">
                        <strong>{{ $session->subjectAssignment->faculty->user->name }}</strong>
                        <div class="muted">{{ $session->subjectAssignment->subject->subject_name }} | submitted {{ $session->submitted_at?->format('d M Y h:i A') }}</div>
                    </div>
                @empty
                    <p class="muted">No late submissions today.</p>
                @endforelse
            </section>

            <section class="card">
                <h2>Pending Extra Lectures</h2>
                @forelse($pendingRequests as $request)
                    <div class="list-divider">
                        <strong>{{ $request->subjectAssignment->subject->subject_name }}</strong>
                        <div class="muted">{{ $request->subjectAssignment->classSection->display_name }} | {{ $request->faculty->user->name }} | {{ $request->requested_date->format('d M Y') }}</div>
                        <div class="actions" style="margin-top: 0.625rem;">
                            <form method="post" action="{{ route('extra-lectures.decide', $request) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="approval_status" value="approved">
                                <button class="button" type="submit">Approve</button>
                            </form>
                            <form method="post" action="{{ route('extra-lectures.decide', $request) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="approval_status" value="rejected">
                                <button class="button danger" type="submit">Reject</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="muted">No pending requests.</p>
                @endforelse
            </section>

            <section class="card">
                <h2>Recent Sessions</h2>
                <table>
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($recentSessions as $session)
                        <tr>
                            <td>{{ $session->lecture_date->format('d M') }}</td>
                            <td>
                                {{ $session->subjectAssignment->subject->subject_name }}
                                <div class="muted">{{ $session->subjectAssignment->classSection->display_name }}</div>
                            </td>
                            <td><span class="badge">{{ $session->status }}</span></td>
                            <td><a class="button secondary" href="{{ route('attendance.show', $session) }}">Open</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </section>
        </div>

        <section class="card" style="margin-top: 1rem;">
            <h2>Official Academic Notice Board</h2>
            @forelse($notifications as $notification)
                @php
                    $isUrgent = Str::contains(strtolower($notification->title), ['defaulter', 'low', 'warning', 'urgent', 'rejected']);
                    $stamp = $isUrgent ? 'URGENT ALERT' : 'OFFICIAL NOTICE';
                    $noticeColor = $isUrgent ? 'var(--color-scsa-danger)' : 'var(--color-scsa-gold)';
                    $pinnedClass = $isUrgent ? 'notice-pinned' : '';
                @endphp
                <div class="list-divider {{ $pinnedClass }}" style="padding: 0.75rem 0.5rem; border-radius: 0.375rem; margin-bottom: 0.5rem; transition: background 0.1s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <span class="university-stamp" style="font-size: 0.625rem; border-color: {{ $noticeColor }}; color: {{ $noticeColor }};">{{ $stamp }}</span>
                        <div class="muted" style="font-size: 0.75rem;">{{ $notification->created_at->format('d M Y') }}</div>
                    </div>
                    <strong style="font-size: 0.875rem; color: var(--color-scsa-ink); display: block;">{{ $notification->title }}</strong>
                    <div class="muted" style="margin-top: 0.25rem; line-height: 1.4; font-size: 0.8125rem;">{{ $notification->message }}</div>
                </div>
            @empty
                <p class="muted" style="text-align: center; padding: 2rem 0;">No official notices or academic alerts posted at this time.</p>
            @endforelse
        </section>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // 1. Daily Averages Chart
                const dailyCtx = document.getElementById('dailyAveragesChart').getContext('2d');
                new Chart(dailyCtx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($dailyDates) !!},
                        datasets: [{
                            label: 'Average Presence %',
                            data: {!! json_encode($dailyPercentages) !!},
                            borderColor: 'rgb(13, 148, 136)',
                            backgroundColor: 'rgba(13, 148, 136, 0.08)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { min: 0, max: 100, ticks: { font: { size: 9 } } },
                            x: { ticks: { font: { size: 9 } } }
                        }
                    }
                });

                // 2. Subject Averages Chart
                const subjectCtx = document.getElementById('subjectAveragesChart').getContext('2d');
                const subjectPercentages = {!! json_encode($subjectPercentages) !!};
                new Chart(subjectCtx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($subjectCodes) !!},
                        datasets: [{
                            data: subjectPercentages,
                            backgroundColor: subjectPercentages.map(p => p < 75 ? 'rgba(185, 28, 28, 0.75)' : 'rgba(13, 148, 136, 0.75)'),
                            borderColor: subjectPercentages.map(p => p < 75 ? 'rgb(185, 28, 28)' : 'rgb(13, 148, 136)'),
                            borderWidth: 1.5,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { min: 0, max: 100, ticks: { font: { size: 9 } } },
                            x: { ticks: { font: { size: 9 } } }
                        }
                    }
                });

                // 3. Monthly Trends Chart
                const monthlyCtx = document.getElementById('monthlyAveragesChart').getContext('2d');
                new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($monthlyLabels) !!},
                        datasets: [{
                            label: 'Monthly Average %',
                            data: {!! json_encode($monthlyPercentages) !!},
                            borderColor: 'rgb(217, 119, 6)',
                            backgroundColor: 'rgba(217, 119, 6, 0.08)',
                            fill: true,
                            tension: 0.1,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { min: 0, max: 100, ticks: { font: { size: 9 } } },
                            x: { ticks: { font: { size: 9 } } }
                        }
                    }
                });
            });
        </script>
    @endif

    @if(auth()->user()->isFaculty())
        <div class="grid grid-2">
            <section class="card">
                <h2>Today's Lectures</h2>
                <table>
                    <thead>
                    <tr>
                        <th>Time</th>
                        <th>Lecture</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($todaySessions as $session)
                        <tr>
                            <td>{{ substr($session->start_time, 0, 5) }} - {{ substr($session->end_time, 0, 5) }}</td>
                            <td>
                                {{ $session->subjectAssignment->subject->subject_name }}
                                <div class="muted">{{ $session->subjectAssignment->classSection->display_name }} | Lecture {{ $session->lecture_no ?? '-' }}</div>
                            </td>
                            <td><span class="badge">{{ $session->status }}</span></td>
                            <td>
                                @if($session->status === 'scheduled' || $session->status === 'pending')
                                    <a class="button" href="{{ route('attendance.show', $session) }}" style="font-size: 0.75rem; min-height: unset; padding: 0.35rem 0.75rem; background: var(--color-scsa-accent); border-color: var(--color-scsa-accent);">⚡ Mark Attendance</a>
                                @else
                                    <a class="button secondary" href="{{ route('attendance.show', $session) }}" style="font-size: 0.75rem; min-height: unset; padding: 0.35rem 0.75rem;">Open</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No lectures scheduled today.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>

            <section class="card">
                <div class="actions" style="justify-content: space-between; margin-bottom: 0.75rem;">
                    <h2 style="margin-bottom: 0;">Extra Lectures</h2>
                    <a class="button" href="{{ route('extra-lectures.create') }}">New Request</a>
                </div>
                @forelse($extraRequests as $request)
                    <div class="list-divider">
                        <strong>{{ $request->subjectAssignment->subject->subject_name }}</strong>
                        <div class="muted">{{ $request->requested_date->format('d M Y') }} | {{ substr($request->start_time, 0, 5) }} - {{ substr($request->end_time, 0, 5) }}</div>
                        <span class="badge {{ $request->approval_status === 'approved' ? 'success' : ($request->approval_status === 'rejected' ? 'danger' : 'warning') }}">
                            {{ $request->approval_status }}
                        </span>
                    </div>
                @empty
                    <p class="muted">No requests submitted yet.</p>
                @endforelse
            </section>
        </div>

        <section class="card" style="margin-top: 1rem;">
            <h2>Official Academic Notice Board</h2>
            @forelse($notifications as $notification)
                @php
                    $isUrgent = Str::contains(strtolower($notification->title), ['defaulter', 'low', 'warning', 'urgent', 'rejected']);
                    $stamp = $isUrgent ? 'URGENT ALERT' : 'OFFICIAL NOTICE';
                    $noticeColor = $isUrgent ? 'var(--color-scsa-danger)' : 'var(--color-scsa-gold)';
                    $pinnedClass = $isUrgent ? 'notice-pinned' : '';
                @endphp
                <div class="list-divider {{ $pinnedClass }}" style="padding: 0.75rem 0.5rem; border-radius: 0.375rem; margin-bottom: 0.5rem; transition: background 0.1s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <span class="university-stamp" style="font-size: 0.625rem; border-color: {{ $noticeColor }}; color: {{ $noticeColor }};">{{ $stamp }}</span>
                        <div class="muted" style="font-size: 0.75rem;">{{ $notification->created_at->format('d M Y') }}</div>
                    </div>
                    <strong style="font-size: 0.875rem; color: var(--color-scsa-ink); display: block;">{{ $notification->title }}</strong>
                    <div class="muted" style="margin-top: 0.25rem; line-height: 1.4; font-size: 0.8125rem;">{{ $notification->message }}</div>
                </div>
            @empty
                <p class="muted" style="text-align: center; padding: 2rem 0;">No official notices or academic alerts posted at this time.</p>
            @endforelse
        </section>
    @endif

    @if(auth()->user()->isStudent())
        <!-- Premium Analytics Styles -->
        <style>
            .progress-ring {
                position: relative;
                width: 140px;
                height: 140px;
                margin: 0 auto 1rem;
            }
            .progress-ring svg {
                transform: rotate(-90deg);
                width: 100%;
                height: 100%;
            }
            .progress-ring circle {
                fill: transparent;
                stroke-width: 10;
            }
            .progress-ring circle.bg {
                stroke: #e2e8f0;
            }
            .progress-ring circle.progress {
                stroke-linecap: round;
                transition: stroke-dashoffset 0.5s ease-in-out;
            }
            .progress-value {
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                font-size: 1.75rem;
                font-weight: 800;
                letter-spacing: -0.02em;
            }
            .progress-value .sub {
                font-size: 0.75rem;
                font-weight: 500;
                color: #64748b;
                text-transform: uppercase;
                margin-top: 0.125rem;
            }
            .safe-badge {
                animation: pulse 2s infinite;
            }
            @keyframes pulse {
                0% { box-shadow: 0 0 0 0 rgba(4, 120, 87, 0.4); }
                70% { box-shadow: 0 0 0 8px rgba(4, 120, 87, 0); }
                100% { box-shadow: 0 0 0 0 rgba(4, 120, 87, 0); }
            }
            .danger-badge {
                animation: pulse-danger 2s infinite;
            }
            @keyframes pulse-danger {
                0% { box-shadow: 0 0 0 0 rgba(185, 28, 28, 0.4); }
                70% { box-shadow: 0 0 0 8px rgba(185, 28, 28, 0); }
                100% { box-shadow: 0 0 0 0 rgba(185, 28, 28, 0); }
            }
        </style>

        <!-- Premium Student Header Metrics -->
        <div class="grid grid-4" style="margin-bottom: 1.5rem;">
            <!-- Circular Progress Card -->
            <div class="card" style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 1.5rem;">
                <div class="progress-ring">
                    <svg viewBox="0 0 120 120">
                        <circle class="bg" cx="60" cy="60" r="50"></circle>
                        @php
                            $circumference = 2 * pi() * 50;
                            $strokeOffset = $circumference - ($circumference * $overallPercentage) / 100;
                            $strokeColor = $overallPercentage < 75 ? 'var(--color-scsa-danger)' : 'var(--color-scsa-accent)';
                        @endphp
                        <circle class="progress" cx="60" cy="60" r="50"
                                stroke="{{ $strokeColor }}"
                                stroke-dasharray="{{ $circumference }}"
                                stroke-dashoffset="{{ $strokeOffset }}"></circle>
                    </svg>
                    <div class="progress-value" style="color: {{ $strokeColor }};">
                        {{ $overallPercentage }}%
                        <span class="sub">Overall</span>
                    </div>
                </div>
                <div class="muted">Conducted: <strong>{{ $overallConducted }}</strong> | Present: <strong>{{ $overallPresent }}</strong></div>
            </div>

            <!-- Exam Eligibility Card -->
            <div class="card" style="grid-column: span 2; display: flex; flex-direction: column; justify-content: space-between; padding: 1.5rem; border-color: {{ $overallPercentage < 75 ? 'var(--color-scsa-danger)' : 'var(--color-scsa-accent)' }}; border-width: 1px; border-left-width: 5px;">
                <div>
                    <h2>End-Semester Examination Status</h2>
                    @if($overallPercentage < 75)
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                            <span class="badge danger danger-badge" style="padding: 0.4rem 0.875rem; font-size: 0.8125rem; text-transform: uppercase; letter-spacing: 0.05em;">Defaulter Status</span>
                            <span style="font-weight: 700; color: var(--color-scsa-danger);">Hall Ticket Locked</span>
                        </div>
                        <p class="muted" style="font-size: 0.9375rem; line-height: 1.5; margin: 0;">
                            Your overall attendance is currently below the **mandatory 75% criteria**. Your End-Semester Hall Ticket is locked.
                        </p>
                        <div style="margin-top: 0.75rem; padding: 0.5rem 0.75rem; background: rgba(185, 28, 28, 0.05); border-left: 3px solid var(--color-scsa-danger); border-radius: 4px; font-size: 0.8125rem; color: var(--color-scsa-danger); font-weight: 600;">
                            ⚠️ Action Required: You must attend the next <strong>{{ $overallToAttend }}</strong> consecutive lectures to recover your eligibility.
                        </div>
                    @else
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                            <span class="badge success safe-badge" style="padding: 0.4rem 0.875rem; font-size: 0.8125rem; text-transform: uppercase; letter-spacing: 0.05em;">Eligible</span>
                            <span style="font-weight: 700; color: var(--color-scsa-success);">Exam Hall Ticket Unlocked</span>
                        </div>
                        <p class="muted" style="font-size: 0.9375rem; line-height: 1.5; margin: 0;">
                            Your attendance matches the mandatory **75% Shreyarth University criteria**. You are fully cleared to receive your final examination hall ticket.
                        </p>
                        <div style="margin-top: 0.75rem; padding: 0.5rem 0.75rem; background: rgba(4, 120, 87, 0.05); border-left: 3px solid var(--color-scsa-success); border-radius: 4px; font-size: 0.8125rem; color: var(--color-scsa-success); font-weight: 500;">
                            💡 Safe Zone: You can safely miss up to <strong>{{ $overallToSkip }}</strong> consecutive lectures without falling below the 75% limit.
                        </div>
                    @endif
                </div>
                <div style="border-top: 1px solid var(--color-scsa-line); padding-top: 0.75rem; margin-top: 0.75rem; font-size: 0.8125rem;" class="muted">
                    Defaulters (below 75%) are barred from SCSA final semester examinations as per University policy.
                </div>
            </div>

            <!-- Subject-wise Visual Chart -->
            <div class="card" style="padding: 1rem; display: flex; flex-direction: column; justify-content: center;">
                <h2 style="font-size: 0.9375rem; margin-bottom: 0.5rem; text-align: center;">Subject Attendance Distribution</h2>
                <div style="height: 120px; position: relative;">
                    <canvas id="subjectChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-2">
            <!-- Subject-wise Detailed Table -->
            <section class="card">
                <h2>Subject-wise Analysis & Action Plans</h2>
                <table>
                    <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Present</th>
                        <th>Conducted</th>
                        <th>Percentage</th>
                        <th>Action Plan / Safe-Zone</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($attendanceSummary as $row)
                        <tr>
                            <td>
                                <strong>{{ $row->subject_name }}</strong>
                                <div class="muted">{{ $row->subject_code }}</div>
                            </td>
                            <td>{{ $row->present_count }}</td>
                            <td>{{ $row->conducted_count }}</td>
                            <td>
                                <span class="badge {{ $row->percentage < 75 ? 'danger' : 'success' }}">{{ $row->percentage }}%</span>
                            </td>
                            <td class="muted" style="font-size: 0.8125rem;">
                                @if($row->percentage < 75)
                                    <span style="color: var(--color-scsa-danger); font-weight: 600;">Attend next {{ $row->consecutive_to_attend }} consecutive</span>
                                @else
                                    <span style="color: var(--color-scsa-success); font-weight: 500;">Can skip next {{ $row->safe_to_skip }} safely</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">No attendance records available yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>

            <!-- Notifications Card (University Notice Board) -->
            <section class="card">
                <h2>Official Academic Notice Board</h2>
                @forelse($notifications as $notification)
                    @php
                        $isUrgent = Str::contains(strtolower($notification->title), ['defaulter', 'low', 'warning', 'urgent', 'rejected']);
                        $stamp = $isUrgent ? 'URGENT ALERT' : 'OFFICIAL NOTICE';
                        $noticeColor = $isUrgent ? 'var(--color-scsa-danger)' : 'var(--color-scsa-gold)';
                        $pinnedClass = $isUrgent ? 'notice-pinned' : '';
                    @endphp
                    <div class="list-divider {{ $pinnedClass }}" style="padding: 0.75rem 0.5rem; border-radius: 0.375rem; margin-bottom: 0.5rem; transition: background 0.1s ease;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                            <span class="university-stamp" style="font-size: 0.625rem; border-color: {{ $noticeColor }}; color: {{ $noticeColor }};">{{ $stamp }}</span>
                            <div class="muted" style="font-size: 0.75rem;">{{ $notification->created_at->format('d M Y') }}</div>
                        </div>
                        <strong style="font-size: 0.875rem; color: var(--color-scsa-ink); display: block;">{{ $notification->title }}</strong>
                        <div class="muted" style="margin-top: 0.25rem; line-height: 1.4; font-size: 0.8125rem;">{{ $notification->message }}</div>
                    </div>
                @empty
                    <p class="muted" style="text-align: center; padding: 2rem 0;">No official notices or academic alerts posted at this time.</p>
                @endforelse
            </section>

            <!-- Datewise Attendance -->
            <section class="card" style="grid-column: 1 / -1;">
                <div class="actions" style="justify-content: space-between; margin-bottom: 0.875rem;">
                    <h2 style="margin-bottom: 0;">Datewise Attendance Sheet</h2>
                    <form method="get" action="{{ route('dashboard') }}" class="actions">
                        <input type="date" name="attendance_date" value="{{ $selectedAttendanceDate }}" style="width: 12rem;">
                        <button class="button" type="submit">Apply</button>
                    </form>
                </div>

                <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table style="min-width: 58rem;">
                        <thead>
                        <tr>
                            <th>Lecture No.</th>
                            <th>Room No.</th>
                            <th>Time</th>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Theory / Practical</th>
                            <th>Faculty Name</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($datewiseAttendance as $session)
                            @php($record = $session->attendanceRecords->first())
                            <tr>
                                <td>{{ $session->lecture_no ?? '-' }}</td>
                                <td>-</td>
                                <td>{{ $session->start_time ? substr($session->start_time, 0, 5).' - '.substr($session->end_time, 0, 5) : '-' }}</td>
                                <td>{{ $session->subjectAssignment->subject->subject_code }}</td>
                                <td>{{ $session->subjectAssignment->subject->subject_name }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $session->session_type)) }}</td>
                                <td>{{ $session->subjectAssignment->faculty->user->name }}</td>
                                <td>
                                    <span class="badge {{ $record?->status === 'present' ? 'success' : ($record?->status === 'absent_with_leave' ? 'warning' : 'danger') }}">
                                        {{ ucwords(str_replace('_', ' ', $record?->status ?? 'not_marked')) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="muted">No data found.!</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Inject Chart.js and build Subject Attendance Bar Chart -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const canvas = document.getElementById('subjectChart');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                
                const subjects = {!! json_encode($attendanceSummary->pluck('subject_code')) !!};
                const percentages = {!! json_encode($attendanceSummary->pluck('percentage')) !!};

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: subjects,
                        datasets: [{
                            label: 'Attendance %',
                            data: percentages,
                            backgroundColor: percentages.map(p => p < 75 ? 'rgba(185, 28, 28, 0.75)' : 'rgba(13, 148, 136, 0.75)'),
                            borderColor: percentages.map(p => p < 75 ? 'rgb(185, 28, 28)' : 'rgb(13, 148, 136)'),
                            borderWidth: 1.5,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) { return context.parsed.y + '%'; }
                                }
                            }
                        },
                        scales: {
                            y: {
                                min: 0,
                                max: 100,
                                ticks: { font: { size: 9 }, stepSize: 25 },
                                grid: { color: 'rgba(0, 0, 0, 0.05)' }
                            },
                            x: {
                                ticks: { font: { size: 9 } },
                                grid: { display: false }
                            }
                        }
                    }
                });
            });
        </script>
    @endif
@endsection
