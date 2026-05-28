@extends('layouts.app')

@section('title', 'Evaluation Unconfigured | SCSA Attendance')
@section('page-title', 'Internal Marks')
@section('page-subtitle')
    {{ $subjectAssignment->subject->subject_name }} | {{ $subjectAssignment->classSection->display_name }}
@endsection

@section('content')
    <div class="card" style="text-align: center; padding: 3rem 1.5rem; max-width: 40rem; margin: 2rem auto;">
        <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">⚙️</span>
        <h2 style="margin-bottom: 0.5rem;">CIE Components Not Configured</h2>
        <p class="muted" style="margin-bottom: 2rem; font-size: 0.9375rem;">
            Before you can enter student marks, you must define the Continuous Internal Evaluation (CIE) components (e.g. Assignments, Quizzes, Class Tests) that sum up to exactly 30 marks.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a class="button" href="{{ route('marks.configure.create', $subjectAssignment) }}">Configure Components</a>
            <a class="button secondary" href="{{ route('marks.index') }}">Go Back</a>
        </div>
    </div>
@endsection
