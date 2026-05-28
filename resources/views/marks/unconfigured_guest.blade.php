@extends('layouts.app')

@section('title', 'Evaluation Unconfigured | SCSA Attendance')
@section('page-title', 'Internal Marks')
@section('page-subtitle')
    {{ $subjectAssignment->subject->subject_name }} | {{ $subjectAssignment->classSection->display_name }}
@endsection

@section('content')
    <div class="card" style="text-align: center; padding: 3rem 1.5rem; max-width: 40rem; margin: 2rem auto;">
        <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">⌛</span>
        <h2 style="margin-bottom: 0.5rem;">Awaiting Configuration</h2>
        <p class="muted" style="margin-bottom: 2rem; font-size: 0.9375rem;">
            The assigned Faculty member (<strong>{{ $subjectAssignment->faculty->user->name }}</strong>) has not configured the evaluation components for this subject yet.
        </p>
        <a class="button" href="{{ route('marks.index') }}">Go Back</a>
    </div>
@endsection
