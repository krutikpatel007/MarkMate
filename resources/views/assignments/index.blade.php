@extends('layouts.app')

@section('title', 'Faculty Assignments | SCSA Attendance')
@section('page-title', 'Faculty assignments')
@section('page-subtitle', 'Each faculty member can teach multiple subjects — grouped below by teacher')

@section('page-actions')
    <a class="button" href="{{ route('assignments.create') }}">Assign subjects to faculty</a>
@endsection

@section('content')
    <div class="card">
        <p class="muted" style="margin: 0 0 1rem; font-size: 0.875rem;">
            {{ $assignmentCount }} assignment record(s) across {{ $facultyGroups->count() }} faculty member(s).
            Add timetable slots per subject after assigning.
        </p>

        @forelse($facultyGroups as $group)
            <div class="list-divider">
                <div class="actions" style="justify-content: space-between; margin-bottom: 0.5rem;">
                    <div>
                        <strong>{{ $group['faculty']->user->name }}</strong>
                        <span class="badge success">{{ $group['subject_count'] }} subject(s)</span>
                    </div>
                    <a class="button secondary" href="{{ route('timetables.faculty', ['faculty_id' => $group['faculty']->id]) }}">Timetable</a>
                </div>
                <table>
                    <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Class</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Used by</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($group['assignments'] as $assignment)
                        <tr>
                            <td>
                                {{ $assignment->subject->subject_name }}
                                <div class="muted">{{ $assignment->subject->subject_code }}</div>
                            </td>
                            <td>{{ $assignment->classSection->display_name }}</td>
                            <td>{{ $assignment->academic_year }}</td>
                            <td><span class="badge {{ $assignment->status === 'active' ? 'success' : 'warning' }}">{{ $assignment->status }}</span></td>
                            <td>
                                <span class="badge">{{ $assignment->timetables_count }} slot(s)</span>
                                <span class="badge">{{ $assignment->lecture_sessions_count }} session(s)</span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="button secondary" href="{{ route('assignments.edit', $assignment) }}">Edit</a>
                                    @if($assignment->status === 'active')
                                        <form method="post" action="{{ route('assignments.status', $assignment) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="inactive">
                                            <button class="button secondary" type="submit">Deactivate</button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('assignments.status', $assignment) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="active">
                                            <button class="button secondary" type="submit">Reactivate</button>
                                        </form>
                                    @endif
                                    @if($assignment->timetables_count === 0 && $assignment->lecture_sessions_count === 0)
                                        <form method="post" action="{{ route('assignments.destroy', $assignment) }}" onsubmit="return confirm('Remove this assignment?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button danger" type="submit">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p class="muted">No faculty assignments yet. Assign one or more subjects to each faculty member.</p>
        @endforelse
    </div>
@endsection
