@extends('layouts.app')

@section('title', 'Extra Lectures | SCSA Attendance')
@section('page-title', 'Extra / Remedial Lectures')
@section('page-subtitle', 'Requests and HOD decisions')

@section('page-actions')
    @if(auth()->user()->isFaculty())
        <a class="button" href="{{ route('extra-lectures.create') }}">New Request</a>
    @endif
@endsection

@section('content')
    <div class="card">
        <table>
            <thead>
            <tr>
                <th>Subject</th>
                <th>Class</th>
                <th>Faculty</th>
                <th>Date / Time</th>
                <th>Status</th>
                @if(auth()->user()->isAdmin() || auth()->user()->isHod())
                    <th>Decision</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @forelse($requests as $request)
                <tr>
                    <td>
                        {{ $request->subjectAssignment->subject->subject_name }}
                        <div class="muted">{{ ucfirst($request->session_type) }}</div>
                    </td>
                    <td>{{ $request->subjectAssignment->classSection->display_name }}</td>
                    <td>{{ $request->faculty->user->name }}</td>
                    <td>
                        {{ $request->requested_date->format('d M Y') }}
                        <div class="muted">{{ substr($request->start_time, 0, 5) }} - {{ substr($request->end_time, 0, 5) }}</div>
                    </td>
                    <td>
                        <span class="badge {{ $request->approval_status === 'approved' ? 'success' : ($request->approval_status === 'rejected' ? 'danger' : 'warning') }}">
                            {{ $request->approval_status }}
                        </span>
                    </td>
                    @if(auth()->user()->isAdmin() || auth()->user()->isHod())
                        <td>
                            @if($request->approval_status === 'pending')
                                <div class="actions">
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
                            @else
                                <span class="muted">{{ $request->approver?->name ?? 'Decided' }}</span>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ auth()->user()->isAdmin() || auth()->user()->isHod() ? 6 : 5 }}" class="muted">No extra lecture requests found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
