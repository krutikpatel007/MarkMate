@extends('layouts.app')

@section('title', 'Departments | SCSA Attendance')
@section('page-title', 'Departments')
@section('page-subtitle', 'Manage university departments')

@section('page-actions')
    <div class="actions">
        <a class="button" href="{{ route('departments.create') }}">Add department</a>
    </div>
@endsection

@section('content')
    <div class="card">
        <table>
            <thead>
            <tr>
                <th>Department code</th>
                <th>Department name</th>
                <th>Programs</th>
                <th>Faculty</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($departments as $dept)
                <tr>
                    <td><strong>{{ $dept->department_code }}</strong></td>
                    <td>{{ $dept->department_name }}</td>
                    <td><a href="{{ route('programs.index', ['department_id' => $dept->id]) }}">{{ $dept->programs_count }}</a></td>
                    <td>{{ $dept->faculty_count }}</td>
                    <td><span class="badge {{ $dept->status === 'active' ? 'success' : 'warning' }}">{{ $dept->status }}</span></td>
                    <td>
                        <div class="actions">
                            <a class="button secondary" href="{{ route('departments.edit', $dept) }}">Edit</a>
                            <form method="post" action="{{ route('departments.destroy', $dept) }}" onsubmit="return confirm('Remove this department?');">
                                @csrf
                                @method('DELETE')
                                <button class="button danger" type="submit">Remove</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No departments yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
