@extends('layouts.app')

@section('title', 'Edit Faculty Assignment | SCSA Attendance')
@section('page-title', 'Edit Faculty Assignment')
@section('page-subtitle', $assignment->subject->subject_name.' | '.$assignment->classSection->display_name)

@section('content')
    <div class="card" style="max-width: 48rem;">
        <form method="post" action="{{ route('assignments.update', $assignment) }}">
            @csrf
            @method('PUT')
            @include('assignments._form', ['assignment' => $assignment])
            <div class="actions">
                <button class="button" type="submit">Update Assignment</button>
                <a class="button secondary" href="{{ route('assignments.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
