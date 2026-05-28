@extends('layouts.app')

@section('title', 'Change Password | SCSA Attendance')
@section('page-title', 'Change Password')
@section('page-subtitle', 'Temporary passwords must be replaced before continuing.')

@section('content')
    <div class="card" style="max-width: 32.5rem;">
        <form method="post" action="{{ route('password.update') }}">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="password">New password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </div>

            <button class="button" type="submit">Update Password</button>
        </form>
    </div>
@endsection
