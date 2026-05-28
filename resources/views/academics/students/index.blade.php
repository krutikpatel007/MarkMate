@extends('layouts.app')

@section('title', 'Students | SCSA Attendance')
@section('page-title', 'Students')
@section('page-subtitle', 'Enroll students manually or bulk upload a full class file')

@section('page-actions')
    <div class="actions" data-motion="fade-up">
        <a class="button secondary" href="{{ route('academics.index') }}">Academic hub</a>
        <a class="button secondary" href="{{ route('academics.students.import.create', array_filter(['class_section_id' => $filterSectionId])) }}">Bulk upload</a>
        <a class="button" href="{{ route('academics.students.create') }}">Add student</a>
    </div>
@endsection

@section('content')
    <form class="card" method="get" action="{{ route('academics.students.index') }}" style="margin-bottom: 1rem;" data-motion="fade-up">
        <div class="field" style="margin-bottom: 0;" data-motion="fade-up">
            <label for="class_section_id">Filter by class</label>
            <select id="class_section_id" name="class_section_id" onchange="this.form.submit()">
                <option value="">All classes</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" @selected($filterSectionId === $section->id)>{{ $section->display_name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="card" data-motion="fade-up">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Enrollment</th>
                <th>Roll</th>
                <th>Class</th>
                <th>Login</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($students as $student)
                <tr>
                    <td>{{ $student->user->name }}</td>
                    <td>{{ $student->enrollment_no }}</td>
                    <td>{{ $student->roll_no ?? '-' }}</td>
                    <td>{{ $student->classSection->display_name }}</td>
                    <td class="muted">{{ $student->user->username }}</td>
                    <td><span class="badge {{ $student->status === 'active' ? 'success' : 'warning' }}">{{ $student->status }}</span></td>
                    <td>
                        <div class="actions" data-motion="fade-up">
                            <a class="button secondary" href="{{ route('academics.students.edit', $student) }}">Edit</a>
                            <form method="post" action="{{ route('academics.students.destroy', $student) }}" onsubmit="return confirm('Remove this student?');">
                                @csrf
                                @method('DELETE')
                                <button class="button danger" type="submit">Remove</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No students found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
