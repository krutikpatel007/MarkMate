@extends('layouts.app')

@section('title', 'Edit Department | SCSA Attendance')
@section('page-title', 'Edit department')
@section('page-subtitle', $department->department_name)

@section('content')
    <form class="card" style="max-width: 45rem;" method="post" action="{{ route('departments.update', $department) }}">
        @csrf
        @method('PUT')
        <div class="field">
            <label for="department_code">Department code</label>
            <input id="department_code" name="department_code" maxlength="20" required value="{{ old('department_code', $department->department_code) }}">
        </div>
        <div class="field">
            <label for="department_name">Department name</label>
            <input id="department_name" name="department_name" maxlength="255" required value="{{ old('department_name', $department->department_name) }}">
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="active" @selected(old('status', $department->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $department->status) === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="actions">
            <button class="button" type="submit">Update department</button>
            <a class="button secondary" href="{{ route('departments.index') }}">Cancel</a>
        </div>
    </form>
@endsection
