@extends('layouts.app')

@section('title', 'Edit Staff User | SCSA Attendance')
@section('page-title', 'Edit Staff User')
@section('page-subtitle', 'Update role, department profile, login details, and active status')

@section('page-actions')
    <a class="button secondary" href="{{ route('staff.index') }}">Back to Staff Users</a>
@endsection

@section('content')
    <form class="card" method="post" action="{{ route('staff.update', $staff) }}">
        @csrf
        @method('PUT')

        @include('staff._form', ['staff' => $staff])

        <div class="actions">
            <button class="button" type="submit">Update Staff User</button>
            <a class="button secondary" href="{{ route('staff.index') }}">Cancel</a>
        </div>
    </form>
@endsection
