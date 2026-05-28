@extends('layouts.app')

@section('title', 'Add Timetable Slot | SCSA Attendance')
@section('page-title', 'Add Timetable Slot')
@section('page-subtitle', 'Link a weekly time to a subject-class-faculty assignment')

@section('content')
    @if($assignments->isEmpty())
        <div class="card">
            <p class="muted">There are no active subject assignments yet. Create an assignment before adding timetable slots.</p>
            <div class="actions">
                <a class="button" href="{{ route('assignments.create') }}">Create Assignment</a>
                <a class="button secondary" href="{{ route('timetables.slots') }}">All Slots</a>
            </div>
        </div>
    @else
        <div class="card" style="max-width: 45rem;">
            <form method="post" action="{{ route('timetables.store') }}">
                @csrf
                @include('timetables._form', ['timetable' => null])
                <div class="actions">
                    <button class="button" type="submit">Save Slot</button>
                    <a class="button secondary" href="{{ route('timetables.slots') }}">Cancel</a>
                </div>
            </form>
        </div>
    @endif
@endsection
