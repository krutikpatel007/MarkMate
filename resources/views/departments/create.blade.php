@extends('layouts.app')

@section('title', 'Add Department | SCSA Attendance')
@section('page-title', 'Add department')
@section('page-subtitle', 'Create a new university department')

@section('content')
    <form class="card" style="max-width: 45rem;" method="post" action="{{ route('departments.store') }}">
        @csrf
        <div class="field">
            <label for="department_code">Department code</label>
            <input id="department_code" name="department_code" maxlength="20" required placeholder="SCSA" value="{{ old('department_code') }}">
        </div>
        <div class="field">
            <label for="department_name">Department name</label>
            <input id="department_name" name="department_name" maxlength="255" required placeholder="School of Computer Science and Applications" value="{{ old('department_name') }}">
        </div>
        <div class="actions">
            <button class="button" type="submit">Save department</button>
            <a class="button secondary" href="{{ route('departments.index') }}">Cancel</a>
        </div>
    </form>
@endsection
