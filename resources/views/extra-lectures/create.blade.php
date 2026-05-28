@extends('layouts.app')

@section('title', 'Request Extra Lecture | SCSA Attendance')
@section('page-title', 'Request Extra Lecture')
@section('page-subtitle', 'HOD approval is required before attendance can be marked.')

@section('content')
    <div class="card" style="max-width: 45rem;">
        <form method="post" action="{{ route('extra-lectures.store') }}">
            @csrf

            <div class="field">
                <label for="subject_assignment_id">Subject and class</label>
                <select id="subject_assignment_id" name="subject_assignment_id" required>
                    <option value="">Select assignment</option>
                    @foreach($assignments as $assignment)
                        <option value="{{ $assignment->id }}" @selected(old('subject_assignment_id') == $assignment->id)>
                            {{ $assignment->subject->subject_name }} | {{ $assignment->classSection->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-2">
                <div class="field">
                    <label for="requested_date">Date</label>
                    <input id="requested_date" name="requested_date" type="date" value="{{ old('requested_date') }}" required>
                </div>

                <div class="field">
                    <label for="session_type">Type</label>
                    <select id="session_type" name="session_type" required>
                        <option value="extra" @selected(old('session_type') === 'extra')>Extra</option>
                        <option value="remedial" @selected(old('session_type') === 'remedial')>Remedial</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="field">
                    <label for="start_time">Start time</label>
                    <input id="start_time" name="start_time" type="time" value="{{ old('start_time') }}" required>
                </div>

                <div class="field">
                    <label for="end_time">End time</label>
                    <input id="end_time" name="end_time" type="time" value="{{ old('end_time') }}" required>
                </div>
            </div>

            <div class="field">
                <label for="reason">Reason</label>
                <textarea id="reason" name="reason" required>{{ old('reason') }}</textarea>
            </div>

            <div class="actions">
                <button class="button" type="submit">Submit Request</button>
                <a class="button secondary" href="{{ route('extra-lectures.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
