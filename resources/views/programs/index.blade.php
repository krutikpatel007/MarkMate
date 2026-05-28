@extends('layouts.app')

@section('title', 'Programs | SCSA Attendance')
@section('page-title', 'Programs')
@section('page-subtitle', 'Manage academic degree programs and their semesters')

@section('page-actions')
    <div class="actions">
        <a class="button secondary" href="{{ route('departments.index') }}">Departments</a>
        <a class="button" href="{{ route('programs.create') }}">Add program</a>
    </div>
@endsection

@section('content')
    <div class="card">
        <table>
            <thead>
            <tr>
                <th>Program code</th>
                <th>Program name</th>
                <th>Department</th>
                <th>Semesters</th>
                <th>Classes</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($programs as $program)
                <tr>
                    <td><strong>{{ $program->program_code }}</strong></td>
                    <td>{{ $program->program_name }}</td>
                    <td>{{ $program->department->department_code }}</td>
                    <td>{{ $program->semesters_count }}</td>
                    <td><a href="{{ route('academics.classes.index', ['program_id' => $program->id]) }}">{{ $program->class_sections_count }}</a></td>
                    <td><span class="badge {{ $program->status === 'active' ? 'success' : 'warning' }}">{{ $program->status }}</span></td>
                    <td>
                        <div class="actions">
                            <a class="button secondary" href="{{ route('programs.edit', $program) }}">Edit</a>
                            <form method="post" action="{{ route('programs.destroy', $program) }}" onsubmit="return confirm('Remove this program and all its semesters?');">
                                @csrf
                                @method('DELETE')
                                <button class="button danger" type="submit">Remove</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No programs yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
