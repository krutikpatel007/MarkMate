@extends('layouts.app')

@section('title', 'Correction Requests | SCSA Attendance')
@section('page-title', 'Attendance Correction Requests')
@section('page-subtitle', auth()->user()->isFaculty() ? 'Your submitted correction requests' : 'Review and decide faculty correction requests')

@section('content')
    <section class="card">
        <table>
            <thead>
            <tr>
                <th>Session</th>
                <th>Faculty</th>
                <th>Reason</th>
                <th>Changes</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($requests as $request)
                <tr>
                    <td>
                        {{ $request->lectureSession->lecture_date->format('d M Y') }}
                        <div class="muted">
                            {{ $request->lectureSession->subjectAssignment->subject->subject_name }}
                            | {{ $request->lectureSession->subjectAssignment->classSection->display_name }}
                        </div>
                    </td>
                    <td>{{ $request->faculty->user->name }}</td>
                    <td>{{ $request->reason }}</td>
                    <td>
                        @foreach($request->requested_changes as $studentId => $change)
                            <span class="badge">{{ $change['from'] }} to {{ $change['to'] }}</span>
                        @endforeach
                    </td>
                    <td>
                        <span class="badge {{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                            {{ $request->status }}
                        </span>
                        @if($request->decider)
                            <div class="muted">By {{ $request->decider->name }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="actions">
                            <a class="button secondary" href="{{ route('attendance.show', $request->lectureSession) }}">Open</a>

                            @if((auth()->user()->isAdmin() || auth()->user()->isHod()) && $request->status === 'pending')
                                <form method="post" action="{{ route('attendance-corrections.decide', $request) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <button class="button" type="submit">Approve</button>
                                </form>

                                <form method="post" action="{{ route('attendance-corrections.decide', $request) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="rejected">
                                    <button class="button danger" type="submit">Reject</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No correction requests found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
