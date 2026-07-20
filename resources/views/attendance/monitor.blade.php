@extends('layouts.app')

@section('title', 'Attendance Monitor | SCSA Attendance')
@section('page-title', 'Attendance Monitor')
@section('page-subtitle', 'HOD view for submissions, pending lectures, late submissions, locked records, and cancelled sessions')

@section('content')
    <div class="grid grid-4">
        <div class="card stat-card"><div class="muted">Total Sessions</div><div class="stat">{{ $summary['total'] }}</div></div>
        <div class="card stat-card"><div class="muted">Submitted</div><div class="stat">{{ $summary['submitted'] }}</div></div>
        <div class="card stat-card"><div class="muted">Pending</div><div class="stat">{{ $summary['pending'] }}</div></div>
        <div class="card stat-card"><div class="muted">Late</div><div class="stat">{{ $summary['late'] }}</div></div>
    </div>

    <section class="card" style="margin-top: 1rem;">
        <form method="get" action="{{ route('attendance.monitor') }}" class="actions">
            <div class="field" style="margin-bottom: 0; min-width: 12rem;">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach(['scheduled', 'pending', 'conducted', 'locked', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="margin-bottom: 0; min-width: 16rem;">
                <label for="class_section_id">Class</label>
                <select id="class_section_id" name="class_section_id">
                    <option value="">All classes</option>
                    @foreach($classSections as $section)
                        <option value="{{ $section->id }}" @selected((int) $selectedClassSectionId === (int) $section->id)>{{ $section->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="margin-bottom: 0; min-width: 16rem;">
                <label for="subject_id">Subject</label>
                <select id="subject_id" name="subject_id">
                    <option value="">All subjects</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected((int) $selectedSubjectId === (int) $subject->id)>
                            {{ $subject->subject_name }}{{ $subject->subject_code ? ' | '.$subject->subject_code : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="margin-bottom: 0; min-width: 10rem;">
                <label for="date_from">From Date</label>
                <input type="date" id="date_from" name="date_from" value="{{ $selectedDateFrom }}">
            </div>
            <div class="field" style="margin-bottom: 0; min-width: 10rem;">
                <label for="date_to">To Date</label>
                <input type="date" id="date_to" name="date_to" value="{{ $selectedDateTo }}">
            </div>
            <button class="button" type="submit">Apply</button>
            <a class="button secondary" href="{{ route('attendance.monitor') }}">Clear</a>
        </form>
    </section>

    <section class="card" style="margin-top: 1rem;">
        <h2>All Attendance Marked by Faculty</h2>
        <table>
            <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Subject and Class</th>
                <th>Faculty</th>
                <th>Status</th>
                <th>Marked</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Leave</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($sessions as $session)
                <tr>
                    <td>{{ $session->lecture_date->format('d M Y') }}</td>
                    <td>{{ $session->start_time ? substr($session->start_time, 0, 5).' - '.substr($session->end_time, 0, 5) : '-' }}</td>
                    <td>
                        {{ $session->subjectAssignment->subject->subject_name }}
                        <div class="muted">{{ $session->subjectAssignment->classSection->display_name }} | {{ ucfirst($session->session_type) }}</div>
                    </td>
                    <td>{{ $session->subjectAssignment->faculty->user->name }}</td>
                    <td><span class="badge {{ $session->status === 'cancelled' ? 'warning' : ($session->status === 'locked' ? 'success' : '') }}">{{ $session->status }}</span></td>
                    <td>{{ $session->attendance_records_count }}</td>
                    <td>{{ $session->present_count }}</td>
                    <td>{{ $session->absent_count }}</td>
                    <td>{{ $session->leave_count }}</td>
                    <td>
                        <div class="actions">
                            <a class="button secondary" href="{{ route('attendance.show', $session) }}">Open</a>
                            @if(in_array($session->status, ['scheduled', 'pending'], true) && $session->attendance_records_count === 0)
                                <form method="post" action="{{ route('attendance.monitor.status', $session) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button class="button secondary" type="submit">Cancel</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="muted">No sessions match the selected filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <div class="grid grid-2" style="margin-top: 1rem;">
        <section class="card">
            <h2>Pending Lectures</h2>
            @forelse($pendingSessions as $session)
                <div class="list-divider">
                    <strong>{{ $session->subjectAssignment->subject->subject_name }}</strong>
                    <div class="muted">{{ $session->lecture_date->format('d M Y') }} | {{ $session->subjectAssignment->classSection->display_name }} | {{ $session->subjectAssignment->faculty->user->name }}</div>
                </div>
            @empty
                <p class="muted">No pending lectures.</p>
            @endforelse
        </section>

        <section class="card">
            <h2>Late Submissions</h2>
            @forelse($lateSubmissions as $session)
                <div class="list-divider">
                    <strong>{{ $session->subjectAssignment->faculty->user->name }}</strong>
                    <div class="muted">{{ $session->subjectAssignment->subject->subject_name }} | submitted {{ $session->submitted_at?->format('d M Y h:i A') }}</div>
                </div>
            @empty
                <p class="muted">No late submissions found.</p>
            @endforelse
        </section>

        <section class="card">
            <h2>Locked Records</h2>
            @forelse($lockedSessions as $session)
                <div class="list-divider">
                    <strong>{{ $session->subjectAssignment->subject->subject_name }}</strong>
                    <div class="muted">{{ $session->lecture_date->format('d M Y') }} | locked {{ $session->locked_at?->format('d M Y h:i A') ?? 'by status' }}</div>
                </div>
            @empty
                <p class="muted">No locked records yet.</p>
            @endforelse
        </section>

        <section class="card">
            <h2>Cancelled / Not Conducted</h2>
            @forelse($cancelledSessions as $session)
                <div class="list-divider">
                    <strong>{{ $session->subjectAssignment->subject->subject_name }}</strong>
                    <div class="muted">{{ $session->lecture_date->format('d M Y') }} | {{ $session->subjectAssignment->classSection->display_name }}</div>
                </div>
            @empty
                <p class="muted">No cancelled or not conducted sessions.</p>
            @endforelse
        </section>
    </div>
@endsection
