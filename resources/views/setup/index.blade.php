@extends('layouts.app')

@section('title', 'Academic Setup | SCSA Attendance')
@section('page-title', 'Academic Setup')
@section('page-subtitle', 'Read-only overview of programs, classes, subjects, and users')

@section('page-actions')
    <a class="button" href="{{ route('academics.index') }}">Manage classes &amp; students</a>
@endsection

@section('content')
    <div class="grid grid-2">
        <section class="card">
            <h2>Programs and Semesters</h2>
            @foreach($programs as $program)
                <div class="list-divider">
                    <strong>{{ $program->program_name }}</strong>
                    <div class="muted">{{ $program->program_code }}</div>
                    <div class="actions" style="margin-top: 0.5rem;">
                        @foreach($program->semesters as $semester)
                            <span class="badge">{{ $semester->semester_name }}</span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>

        <section class="card">
            <h2>Class Sections</h2>
            <table>
                <thead>
                <tr>
                    <th>Class</th>
                    <th>Students</th>
                </tr>
                </thead>
                <tbody>
                @foreach($classSections as $section)
                    <tr>
                        <td>{{ $section->display_name }}</td>
                        <td>{{ $section->students_count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2>Subjects</h2>
            <table>
                <thead>
                <tr>
                    <th>Code</th>
                    <th>Subject</th>
                    <th>Program</th>
                </tr>
                </thead>
                <tbody>
                @foreach($subjects as $subject)
                    <tr>
                        <td>{{ $subject->subject_code }}</td>
                        <td>{{ $subject->subject_name }}</td>
                        <td>{{ $subject->program->program_code }} Sem {{ $subject->semester->semester_no }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2>Users</h2>
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->username }}</td>
                        <td><span class="badge">{{ $user->role }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>
    </div>
@endsection
