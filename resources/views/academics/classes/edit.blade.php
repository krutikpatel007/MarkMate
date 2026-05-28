@extends('layouts.app')

@section('title', 'Edit Class | SCSA Attendance')
@section('page-title', 'Edit class section')
@section('page-subtitle', $section->display_name)

@section('content')
    <form class="card" style="max-width: 45rem;" method="post" action="{{ route('academics.classes.update', $section) }}">
        @csrf
        @method('PUT')
        @include('academics._program_semester', [
            'programs' => $programs,
            'programId' => $section->program_id,
            'semesterId' => $section->semester_id,
        ])
        <div class="field" data-motion="fade-up">
            <label for="section_name">Section letter / code</label>
            <input id="section_name" name="section_name" maxlength="20" required value="{{ old('section_name', $section->section_name) }}">
        </div>
        <div class="field" data-motion="fade-up">
            <label for="display_name">Display name</label>
            <input id="display_name" name="display_name" maxlength="255" required value="{{ old('display_name', $section->display_name) }}">
        </div>
        <div class="field" data-motion="fade-up">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="active" @selected(old('status', $section->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $section->status) === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="actions" data-motion="fade-up">
            <button class="button" type="submit">Update class</button>
            <a class="button secondary" href="{{ route('academics.classes.index') }}">Cancel</a>
        </div>
    </form>
@endsection
