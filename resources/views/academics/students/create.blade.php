@extends('layouts.app')

@section('title', 'Add Student | SCSA Attendance')
@section('page-title', 'Add student')
@section('page-subtitle', 'Create login and enrollment for a class section')

@section('content')
    <form class="card" style="max-width: 45rem;" method="post" action="{{ route('academics.students.store') }}">
        @csrf
        <div class="field" data-motion="fade-up">
            <label for="class_section_id">Class section</label>
            <select id="class_section_id" name="class_section_id" required>
                <option value="">Select class</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" @selected((int) old('class_section_id') === (int) $section->id)>
                        {{ $section->display_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field" data-motion="fade-up">
            <label for="name">Full name</label>
            <input id="name" name="name" required value="{{ old('name') }}">
        </div>
        <div class="grid grid-2" data-motion="fade-up">
            <div class="field" data-motion="fade-up">
                <label for="enrollment_no">Enrollment number</label>
                <input id="enrollment_no" name="enrollment_no" required value="{{ old('enrollment_no') }}">
            </div>
            <div class="field" data-motion="fade-up">
                <label for="roll_no">Roll number</label>
                <input id="roll_no" name="roll_no" value="{{ old('roll_no') }}">
            </div>
        </div>
        <div class="grid grid-2" data-motion="fade-up">
            <div class="field" data-motion="fade-up">
                <label for="username">Login username</label>
                <input id="username" name="username" required value="{{ old('username') }}">
            </div>
            <div class="field" data-motion="fade-up">
                <label for="email">Email <span class="muted">(optional)</span></label>
                <input id="email" name="email" type="email" value="{{ old('email') }}">
            </div>
        </div>
        <div class="field" data-motion="fade-up">
            <label for="mobile">Mobile <span class="muted">(optional)</span></label>
            <input id="mobile" name="mobile" value="{{ old('mobile') }}">
        </div>
        <div class="field" data-motion="fade-up">
            <label for="password">Initial password <span class="muted">(leave blank for student123)</span></label>
            <input id="password" name="password" type="password" autocomplete="new-password">
        </div>
        <div class="field" data-motion="fade-up">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
        </div>
        <div class="actions" data-motion="fade-up">
            <button class="button" type="submit">Save student</button>
            <a class="button secondary" href="{{ route('academics.students.index') }}">Cancel</a>
        </div>
    </form>
@endsection
