@extends('layouts.app')

@section('title', 'Heatmap Details | SCSA Attendance')
@section('page-title', 'Compliance Details: ' . $formattedDate)
@section('page-subtitle', 'Detailed compliance audit of all scheduled lectures for this date.')

@section('content')
    <div class="grid grid-1">
        <section class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <h2>Scheduled Sessions &amp; Submission Log</h2>
                <a class="button secondary" href="{{ route('attendance.heatmap') }}" style="min-height: unset; padding: 0.4rem 1rem; font-size: 0.8125rem;">
                    &larr; Back to Heatmap
                </a>
            </div>

            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table>
                    <thead>
                    <tr>
                        <th>Faculty</th>
                        <th>Subject</th>
                        <th>Class Section</th>
                        <th>Period &amp; Time</th>
                        <th>Status</th>
                        <th>Compliance Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($sessions as $sess)
                        <tr class="list-divider">
                            <td>
                                <strong>{{ $sess->subjectAssignment->faculty->user->name }}</strong>
                                <div class="muted">Code: {{ $sess->subjectAssignment->faculty->employee_code }}</div>
                            </td>
                            <td>
                                <strong>{{ $sess->subjectAssignment->subject->subject_name }}</strong>
                                <div class="muted">Code: {{ $sess->subjectAssignment->subject->subject_code }}</div>
                            </td>
                            <td>
                                <strong>{{ $sess->subjectAssignment->classSection->display_name }}</strong>
                            </td>
                            <td style="white-space: nowrap;">
                                <strong>Period {{ $sess->lecture_no }}</strong>
                                <div class="muted">{{ substr($sess->start_time, 0, 5) }} – {{ substr($sess->end_time, 0, 5) }}</div>
                            </td>
                            <td>
                                @if(in_array($sess->status, ['conducted', 'locked']))
                                    <span class="badge success">Marked</span>
                                @else
                                    <span class="badge warning">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if(in_array($sess->status, ['conducted', 'locked']))
                                    <span class="badge success" style="background: rgba(16, 185, 129, 0.15); color: #065f46;">
                                        ✅ Submitted on time
                                    </span>
                                @else
                                    <span class="badge danger" style="background: rgba(239, 68, 68, 0.15); color: #991b1b; font-weight: bold;">
                                        🚨 Overdue / Pending
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted" style="text-align: center; padding: 3rem 0;">
                                No lectures were scheduled or recorded on this date.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
