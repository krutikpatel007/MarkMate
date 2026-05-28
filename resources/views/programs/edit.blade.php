@extends('layouts.app')

@section('title', 'Edit Program | SCSA Attendance')
@section('page-title', 'Edit program')
@section('page-subtitle', $program->program_name)

@section('content')
    <form class="card" style="max-width: 45rem;" method="post" action="{{ route('programs.update', $program) }}">
        @csrf
        @method('PUT')
        <div class="field">
            <label for="department_id">Department</label>
            <select id="department_id" name="department_id" required>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected(old('department_id', $program->department_id) == $dept->id)>{{ $dept->department_code }} — {{ $dept->department_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="program_code">Program code</label>
            <input id="program_code" name="program_code" maxlength="20" required value="{{ old('program_code', $program->program_code) }}">
        </div>
        <div class="field">
            <label for="program_name">Program name</label>
            <input id="program_name" name="program_name" maxlength="255" required value="{{ old('program_name', $program->program_name) }}">
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="active" @selected(old('status', $program->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $program->status) === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="actions">
            <button class="button" type="submit">Update program</button>
            <a class="button secondary" href="{{ route('programs.index') }}">Cancel</a>
        </div>
    </form>
@endsection
