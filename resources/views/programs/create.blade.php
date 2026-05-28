@extends('layouts.app')

@section('title', 'Add Program | SCSA Attendance')
@section('page-title', 'Add program')
@section('page-subtitle', 'Create a new degree program with semesters')

@section('content')
    <form class="card" style="max-width: 45rem;" method="post" action="{{ route('programs.store') }}">
        @csrf
        <div class="field">
            <label for="department_id">Department</label>
            <select id="department_id" name="department_id" required>
                <option value="">Select department</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->department_code }} — {{ $dept->department_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="program_code">Program code</label>
            <input id="program_code" name="program_code" maxlength="20" required placeholder="BCA" value="{{ old('program_code') }}">
        </div>
        <div class="field">
            <label for="program_name">Program name</label>
            <input id="program_name" name="program_name" maxlength="255" required placeholder="Bachelor of Computer Applications" value="{{ old('program_name') }}">
        </div>
        <div class="field">
            <label for="semester_count">Number of semesters to create</label>
            <input id="semester_count" name="semester_count" type="number" min="1" max="16" required placeholder="10" value="{{ old('semester_count', 10) }}">
            <span class="muted" style="font-size: 0.8125rem;">Semesters 1 through this number will be auto-created.</span>
        </div>
        <div class="actions">
            <button class="button" type="submit">Save program</button>
            <a class="button secondary" href="{{ route('programs.index') }}">Cancel</a>
        </div>
    </form>
@endsection
