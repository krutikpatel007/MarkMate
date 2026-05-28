@extends('layouts.app')

@section('title', 'Timetable Slots | SCSA Attendance')
@section('page-title', 'All Timetable Slots')
@section('page-subtitle', 'Edit or remove individual weekly slots')

@section('page-actions')
    <div class="actions">
        <a class="button secondary" href="{{ route('timetables.index') }}">Class-wise Grid</a>
        <a class="button secondary" href="{{ route('timetables.faculty') }}">Faculty-wise View</a>
        @if(auth()->user()->isAdmin() || auth()->user()->isHod())
            <a class="button secondary" href="{{ route('assignments.index') }}">Faculty Assignments</a>
            <a class="button" href="{{ route('timetables.create') }}">Add Slot</a>
        @endif
    </div>
@endsection

@section('content')
    <div class="card">
        <table>
            <thead>
            <tr>
                <th>Day</th>
                <th>Time</th>
                <th>Type</th>
                <th>Lecture</th>
                <th>Subject and Class</th>
                <th>Faculty</th>
                <th>Year</th>
                <th>Status</th>
                @if(auth()->user()->isAdmin() || auth()->user()->isHod())
                    <th></th>
                @endif
            </tr>
            </thead>
            <tbody>
            @forelse($timetables as $row)
                <tr>
                    <td>{{ $dayNames[$row->day_of_week] ?? $row->day_of_week }}</td>
                    <td>{{ substr($row->start_time, 0, 5) }} – {{ substr($row->end_time, 0, 5) }}</td>
                    <td><span class="badge {{ ($row->slot_type ?? 'regular') === 'lab' ? 'success' : '' }}">{{ $row->slot_type ?? 'regular' }}</span></td>
                    <td>{{ $row->lecture_no }}</td>
                    <td>
                        {{ $row->subjectAssignment->subject->subject_name }}
                        <div class="muted">{{ $row->subjectAssignment->classSection->display_name }}</div>
                    </td>
                    <td>{{ $row->subjectAssignment->faculty->user->name }}</td>
                    <td>{{ $row->subjectAssignment->academic_year }}</td>
                    <td><span class="badge {{ $row->status === 'active' ? 'success' : 'warning' }}">{{ $row->status }}</span></td>
                    @if(auth()->user()->isAdmin() || auth()->user()->isHod())
                        <td>
                            <div class="actions">
                                <a class="button secondary" href="{{ route('timetables.edit', $row) }}">Edit</a>
                                <form method="post" action="{{ route('timetables.destroy', $row) }}" onsubmit="return confirm('Remove this timetable slot?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="9" class="muted">No timetable slots yet. Add one to schedule weekly lectures.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
