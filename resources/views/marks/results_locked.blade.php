@extends('layouts.app')

@section('title', 'Results | SCSA Attendance')
@section('page-title', 'Semester Results')
@section('page-subtitle', 'Official academic performance statement')

@section('content')
    <div style="max-width: 600px; margin: 4rem auto; text-align: center;">
        <div class="card" style="padding: 4rem 2rem; border-radius: 1rem; border: 1px solid var(--color-scsa-line); box-shadow: var(--shadow-md); background-color: var(--bg-secondary);">
            <span style="font-size: 4rem; display: block; margin-bottom: 1.5rem; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.05));">🔒</span>
            <h2 style="margin-bottom: 0.75rem; font-weight: 800; color: var(--color-scsa-ink); border-bottom: 0; padding-bottom: 0; font-family: var(--font-display);">Results Awaiting Declaration</h2>
            <p class="muted" style="max-width: 28rem; margin: 0 auto 2rem auto; font-size: 0.95rem; line-height: 1.6;">
                The examination marks and final semester grade reports for <strong>{{ $student->classSection->display_name }}</strong> have not been officially declared/released by the Examination Department yet.
            </p>
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: var(--bg-primary); padding: 0.75rem 1.25rem; border-radius: var(--border-radius-lg); border: 1px solid var(--color-scsa-line); font-size: 0.85rem; font-weight: 600; color: var(--color-scsa-muted);">
                ℹ️ Status: Under Evaluation & Scrutiny
            </div>
        </div>
    </div>
@endsection
