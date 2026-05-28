@extends('layouts.app')

@section('title', 'Subjects | SCSA Attendance')
@section('page-title', 'Subjects')
@section('page-subtitle', 'Manage subject codes and names per semester')

@section('page-actions')
    <div class="actions" data-motion="fade-up">
        <a class="button secondary" href="{{ route('academics.index') }}">Academic hub</a>
        <a class="button" href="{{ route('academics.subjects.create') }}">Add subject</a>
    </div>
@endsection

@section('content')
    <div class="card" data-motion="fade-up">
        <table>
            <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Program</th>
                <th>Semester</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($subjects as $subject)
                <tr>
                    <td>{{ $subject->subject_code }}</td>
                    <td>{{ $subject->subject_name }}</td>
                    <td>{{ $subject->program->program_code }}</td>
                    <td>{{ $subject->semester->semester_name }}</td>
                    <td><span class="badge {{ $subject->status === 'active' ? 'success' : 'warning' }}">{{ $subject->status }}</span></td>
                    <td>
                        <div class="actions" data-motion="fade-up">
                            <a class="button secondary" href="{{ route('academics.subjects.edit', $subject) }}">Edit</a>
                            <form method="post" action="{{ route('academics.subjects.destroy', $subject) }}" onsubmit="return confirm('Remove this subject?');">
                                @csrf
                                @method('DELETE')
                                <button class="button danger" type="submit">Remove</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No subjects yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
