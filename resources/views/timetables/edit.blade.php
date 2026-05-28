@extends('layouts.app')

@section('title', 'Edit Timetable Slot | SCSA Attendance')
@section('page-title', 'Edit Timetable Slot')
@section('page-subtitle', $timetable->subjectAssignment->subject->subject_name.' | '.$timetable->subjectAssignment->classSection->display_name)

@section('content')
    <div class="card" style="max-width: 45rem;">
        <form method="post" action="{{ route('timetables.update', $timetable) }}">
            @csrf
            @method('PUT')
            @include('timetables._form', ['timetable' => $timetable])
            <div class="actions">
                <button class="button" type="submit">Update Slot</button>
                <a class="button secondary" href="{{ route('timetables.index', ['class_section_id' => $timetable->subjectAssignment->class_section_id]) }}">Class-wise Grid</a>
                <a class="button secondary" href="{{ route('timetables.slots') }}">All Slots</a>
            </div>
        </form>
    </div>
@endsection
