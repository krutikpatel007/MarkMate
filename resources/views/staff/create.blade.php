@extends('layouts.app')

@section('title', 'New Staff User | SCSA Attendance')
@section('page-title', 'New Staff User')
@section('page-subtitle', 'Create a temporary-login account for HOD or faculty access')

@section('page-actions')
    <a class="button secondary" href="{{ route('staff.index') }}">Back to Staff Users</a>
@endsection

@section('content')
    <form class="card" method="post" action="{{ route('staff.store') }}">
        @csrf

        @include('staff._form', ['staff' => null])

        <div class="actions">
            <button class="button" type="submit">Create Staff User</button>
            <a class="button secondary" href="{{ route('staff.index') }}">Cancel</a>
        </div>
    </form>
@endsection
