@extends('layouts.app')

@section('title', 'Add Class | SCSA Attendance')
@section('page-title', 'Add class section')
@section('page-subtitle', 'Create a new batch under a program and semester')

@section('content')
    <form class="card" style="max-width: 45rem;" method="post" action="{{ route('academics.classes.store') }}">
        @csrf
        @include('academics._program_semester', [
            'programs' => $programs,
            'programId' => null,
            'semesterId' => null,
        ])
        <div class="field" data-motion="fade-up">
            <label for="section_name">Section letter / code</label>
            <input id="section_name" name="section_name" maxlength="20" required placeholder="A" value="{{ old('section_name') }}">
        </div>
        <div class="field" data-motion="fade-up">
            <label for="display_name">Display name <span class="muted">(optional)</span></label>
            <input id="display_name" name="display_name" maxlength="255" placeholder="BCA Sem 1 A" value="{{ old('display_name') }}">
        </div>
        <div class="actions" data-motion="fade-up">
            <button class="button" type="submit">Save class</button>
            <a class="button secondary" href="{{ route('academics.classes.index') }}">Cancel</a>
        </div>
    </form>
@endsection
