@extends('layouts.app')

@section('title', 'Marks Not Submitted | SCSA Attendance')
@section('page-title', 'Internal Marks')
@section('page-subtitle')
    {{ $subjectAssignment->subject->subject_name }} | {{ $subjectAssignment->classSection->display_name }}
@endsection

@section('content')
    <div class="card" style="text-align: center; padding: 3.5rem 2rem; max-width: 40rem; margin: 3rem auto; box-shadow: var(--shadow-lg); border-radius: var(--border-radius-xl);">
        <span style="font-size: 3.5rem; display: block; margin-bottom: 1.25rem;">⏳</span>
        <h2 style="margin-bottom: 0.75rem; font-weight: 800; letter-spacing: -0.02em; color: var(--color-scsa-sidebar);">Marks Under Academic Review</h2>
        <p class="muted" style="margin-bottom: 2.25rem; font-size: 0.95rem; line-height: 1.6; max-width: 32rem; margin-left: auto; margin-right: auto; color: var(--color-scsa-muted);">
            The internal marks for this subject have not been submitted to the **Examination Department** yet. 
            They are currently undergoing entry or review within the academic department by the course Faculty / HOD.
            <br><br>
            Once the HOD approves and clicks <strong>"Submit to Exam Department"</strong>, these marks will become visible and ready to process here.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a class="button secondary" style="padding: 0.65rem 1.75rem;" href="{{ route('marks.index') }}">Go Back to List</a>
        </div>
    </div>
@endsection
