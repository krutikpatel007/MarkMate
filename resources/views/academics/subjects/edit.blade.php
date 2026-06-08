@extends('layouts.app')

@section('title', 'Edit Subject | SCSA Attendance')
@section('page-title', 'Edit subject')
@section('page-subtitle', $subject->subject_code)

@section('content')
    <form class="card" style="max-width: 45rem;" method="post" action="{{ route('academics.subjects.update', $subject) }}">
        @csrf
        @method('PUT')
        @include('academics._program_semester', [
            'programs' => $programs,
            'programId' => $subject->program_id,
            'semesterId' => $subject->semester_id,
        ])
        <div class="field" data-motion="fade-up">
            <label for="subject_code">Subject code</label>
            <input id="subject_code" name="subject_code" maxlength="32" required value="{{ old('subject_code', $subject->subject_code) }}">
        </div>
        <div class="field" data-motion="fade-up">
            <label for="subject_name">Subject name</label>
            <input id="subject_name" name="subject_name" maxlength="255" required value="{{ old('subject_name', $subject->subject_name) }}">
        </div>
        <div class="field" data-motion="fade-up">
            <label for="credits">Credits</label>
            <input type="number" id="credits" name="credits" min="1" max="6" required value="{{ old('credits', $subject->credits) }}">
        </div>
        <div class="field" data-motion="fade-up">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="active" @selected(old('status', $subject->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $subject->status) === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="actions" data-motion="fade-up">
            <button class="button" type="submit">Update subject</button>
            <a class="button secondary" href="{{ route('academics.subjects.index') }}">Cancel</a>
        </div>
    </form>
@endsection
