@extends('layouts.app')

@section('title', 'Faculty-wise Timetable | SCSA Attendance')
@section('page-title', 'Faculty-wise Timetable')
@section('page-subtitle', 'View weekly timetable slots grouped by faculty')

@section('page-actions')
    <div class="actions">
        <a class="button secondary" href="{{ route('timetables.index') }}">Class-wise View</a>
        <a class="button secondary" href="{{ route('timetables.slots') }}">All Slots</a>
        @if(auth()->user()->isAdmin() || auth()->user()->isHod())
            <a class="button" href="{{ route('timetables.create') }}">Add Slot</a>
        @endif
    </div>
@endsection

@section('content')
    @if(auth()->user()->isAdmin() || auth()->user()->isHod())
        <section class="card">
            <form method="get" action="{{ route('timetables.faculty') }}" class="actions">
                <div class="field" style="margin-bottom: 0; min-width: 18rem;">
                    <label for="faculty_id">Faculty</label>
                    <select id="faculty_id" name="faculty_id" onchange="this.form.submit()">
                        @foreach($faculty as $member)
                            <option value="{{ $member->id }}" @selected($selectedFaculty && (int) $selectedFaculty->id === (int) $member->id)>
                                {{ $member->user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </section>
    @endif

    <section class="card" style="margin-top: 1rem;">
        @if(!$selectedFaculty)
            <p class="muted">No faculty records are available.</p>
        @else
            @php($subjectCount = $timetables->pluck('subject_assignment.subject_id')->unique()->count())
            <h2>{{ $selectedFaculty->user->name }}
                <span class="badge success">{{ $subjectCount }} subject(s)</span>
                <span class="badge">{{ $timetables->count() }} slot(s)</span>
            </h2>
            <table>
                <thead>
                <tr>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Lecture</th>
                    <th>Subject</th>
                    <th>Class</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($timetables as $slot)
                    <tr>
                        <td>{{ $dayNames[$slot->day_of_week] ?? $slot->day_of_week }}</td>
                        <td>{{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }}</td>
                        <td>{{ $slot->lecture_no }}</td>
                        <td>{{ $slot->subjectAssignment->subject->subject_name }}</td>
                        <td>{{ $slot->subjectAssignment->classSection->display_name }}</td>
                        <td>
                            @if(auth()->user()->isAdmin() || auth()->user()->isHod())
                                <a class="button secondary" href="{{ route('timetables.edit', $slot) }}">Edit</a>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No timetable slots assigned to this faculty member.</td></tr>
                @endforelse
                </tbody>
            </table>
        @endif
    </section>
@endsection
