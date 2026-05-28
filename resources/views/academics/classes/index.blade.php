@extends('layouts.app')

@section('title', 'Classes | SCSA Attendance')
@section('page-title', 'Class sections')
@section('page-subtitle', 'Manage batches and section names')

@section('page-actions')
    <div class="actions" data-motion="fade-up">
        <a class="button secondary" href="{{ route('academics.index') }}">Academic hub</a>
        <a class="button" href="{{ route('academics.classes.create') }}">Add class</a>
    </div>
@endsection

@section('content')
    <div class="card" data-motion="fade-up">
        <table>
            <thead>
            <tr>
                <th>Display name</th>
                <th>Section</th>
                <th>Program</th>
                <th>Semester</th>
                <th>Students</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($sections as $section)
                <tr>
                    <td><strong>{{ $section->display_name }}</strong></td>
                    <td>{{ $section->section_name }}</td>
                    <td>{{ $section->program->program_code }}</td>
                    <td>{{ $section->semester->semester_name }}</td>
                    <td>
                        <a href="{{ route('academics.students.index', ['class_section_id' => $section->id]) }}">
                            {{ $section->students_count }}
                        </a>
                    </td>
                    <td><span class="badge {{ $section->status === 'active' ? 'success' : 'warning' }}">{{ $section->status }}</span></td>
                    <td>
                        <div class="actions" data-motion="fade-up">
                            <a class="button secondary" href="{{ route('academics.classes.edit', $section) }}">Edit</a>
                            <form method="post" action="{{ route('academics.classes.destroy', $section) }}" onsubmit="return confirm('Remove this class section?');">
                                @csrf
                                @method('DELETE')
                                <button class="button danger" type="submit">Remove</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No class sections yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
