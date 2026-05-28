@extends('layouts.app')

@section('title', 'Add Subject | SCSA Attendance')
@section('page-title', 'Add subject')
@section('page-subtitle', 'Define a subject for a program semester')

@section('content')
    <form class="card" style="max-width: 45rem;" method="post" action="{{ route('academics.subjects.store') }}">
        @csrf
        @include('academics._program_semester', [
            'programs' => $programs,
            'programId' => null,
            'semesterId' => null,
        ])
        <div class="field" data-motion="fade-up">
            <label for="subject_code">Subject code</label>
            <input id="subject_code" name="subject_code" maxlength="32" required placeholder="BCA101" value="{{ old('subject_code') }}">
        </div>
        <div class="field" data-motion="fade-up">
            <label for="subject_name">Subject name</label>
            <input id="subject_name" name="subject_name" maxlength="255" required value="{{ old('subject_name') }}">
        </div>
        <div class="actions" data-motion="fade-up">
            <button class="button" type="submit">Save subject</button>
            <a class="button secondary" href="{{ route('academics.subjects.index') }}">Cancel</a>
        </div>
    </form>
@endsection
