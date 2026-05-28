@extends('layouts.app')

@section('title', 'Edit Student | SCSA Attendance')
@section('page-title', 'Edit student')
@section('page-subtitle', $student->user->name)

@section('content')
    <form class="card" style="max-width: 45rem;" method="post" action="{{ route('academics.students.update', $student) }}">
        @csrf
        @method('PUT')
        <div class="field" data-motion="fade-up">
            <label for="class_section_id">Class section</label>
            <select id="class_section_id" name="class_section_id" required>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" @selected((int) old('class_section_id', $student->class_section_id) === (int) $section->id)>
                        {{ $section->display_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field" data-motion="fade-up">
            <label for="name">Full name</label>
            <input id="name" name="name" required value="{{ old('name', $student->user->name) }}">
        </div>
        <div class="grid grid-2" data-motion="fade-up">
            <div class="field" data-motion="fade-up">
                <label for="enrollment_no">Enrollment number</label>
                <input id="enrollment_no" name="enrollment_no" required value="{{ old('enrollment_no', $student->enrollment_no) }}">
            </div>
            <div class="field" data-motion="fade-up">
                <label for="roll_no">Roll number</label>
                <input id="roll_no" name="roll_no" value="{{ old('roll_no', $student->roll_no) }}">
            </div>
        </div>
        <div class="grid grid-2" data-motion="fade-up">
            <div class="field" data-motion="fade-up">
                <label for="username">Login username</label>
                <input id="username" name="username" required value="{{ old('username', $student->user->username) }}">
            </div>
            <div class="field" data-motion="fade-up">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $student->user->email) }}">
            </div>
        </div>
        <div class="field" data-motion="fade-up">
            <label for="mobile">Mobile</label>
            <input id="mobile" name="mobile" value="{{ old('mobile', $student->mobile) }}">
        </div>
        <div class="field" data-motion="fade-up">
            <label for="password">New password <span class="muted">(optional)</span></label>
            <input id="password" name="password" type="password" autocomplete="new-password">
        </div>
        <div class="field" data-motion="fade-up">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
        </div>
        <div class="field" data-motion="fade-up">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="active" @selected(old('status', $student->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $student->status) === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="actions" data-motion="fade-up">
            <button class="button" type="submit">Update student</button>
            <a class="button secondary" href="{{ route('academics.students.index', ['class_section_id' => $student->class_section_id]) }}">Cancel</a>
        </div>
    </form>
@endsection
