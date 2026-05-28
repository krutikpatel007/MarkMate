@extends('layouts.app')

@section('title', 'My Scorecard | SCSA Attendance')
@section('page-title', 'Internal Marks Scorecard')
@section('page-subtitle', 'Track your mid-term and continuous evaluation progress')

@section('content')
    <!-- Premium Profile Header Card -->
    <div class="card" style="background: linear-gradient(135deg, #134e4a 0%, #0f3d3d 100%); color: #fff; padding: 2rem; border-radius: 1rem; margin-bottom: 2rem; border: 0; position: relative; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(15, 61, 61, 0.3);">
        <div style="position: absolute; right: -50px; bottom: -50px; font-size: 10rem; opacity: 0.05; font-weight: 800; user-select: none;">SCSA</div>
        <div style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
            <!-- Initials Avatar -->
            <div style="width: 4.5rem; height: 4.5rem; border-radius: 50%; background: rgba(255, 255, 255, 0.15); display: flex; align-items: center; justify-content: center; font-size: 1.75rem; font-weight: 700; color: #fff; border: 2px solid rgba(255, 255, 255, 0.25); backdrop-filter: blur(10px);">
                {{ strtoupper(substr($student->user->name, 0, 1)) }}{{ strtoupper(substr(strrchr($student->user->name, ' ') ?: ' ', 1, 1)) }}
            </div>
            <div>
                <h2 style="color: #fff; margin: 0 0 0.25rem 0; font-size: 1.625rem; font-weight: 700; border-bottom: 0; padding-bottom: 0;">{{ $student->user->name }}</h2>
                <div style="font-size: 0.9375rem; opacity: 0.85; font-weight: 500; display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <span>🎓 {{ $student->program->program_name }}</span>
                    <span style="opacity: 0.5;">|</span>
                    <span>🔢 Semester {{ $student->semester->semester_no }}</span>
                    <span style="opacity: 0.5;">|</span>
                    <span>🆔 {{ $student->enrollment_no }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid Layout for Subject Cards -->
    <div class="grid grid-2" style="gap: 1.5rem;">
        @forelse($marks as $mark)
            @php
                $percentage = ($mark->total_50 / 50) * 100;
                $barColor = $percentage >= 80 ? 'var(--color-scsa-success)' : ($percentage >= 50 ? 'var(--color-scsa-gold)' : 'var(--color-scsa-danger)');
            @endphp
            <div class="card subject-card" style="position: relative; overflow: hidden; display: flex; flex-direction: column; min-height: 18rem; padding: 1.5rem; transition: all 0.25s ease;" onmouseover="this.style.transform='translateY(-4px)';" onmouseout="this.style.transform='translateY(0)';">
                <!-- Subject Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem;">
                    <div>
                        <span class="badge" style="background-color: var(--color-scsa-accent-soft); color: var(--color-scsa-accent); margin-bottom: 0.35rem;">
                            {{ $mark->subjectAssignment->subject->subject_code }}
                        </span>
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--color-scsa-ink); margin: 0; font-family: var(--font-display);">{{ $mark->subjectAssignment->subject->subject_name }}</h3>
                        <div class="muted" style="font-size: 0.8125rem; display: flex; align-items: center; gap: 0.35rem; margin-top: 0.25rem;">
                            <span>🧑‍🏫 {{ $mark->subjectAssignment->faculty->user->name }}</span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 0.725rem; text-transform: uppercase; font-weight: 700; color: var(--color-scsa-muted); display: block; margin-bottom: 0.15rem;">CIE Score</span>
                        <strong style="font-size: 1.75rem; color: var(--color-scsa-accent); font-family: var(--font-display);">{{ $mark->total_50 }} <span style="font-weight: 500; font-size: 0.875rem; color: var(--color-scsa-muted);">/ 50</span></strong>
                    </div>
                </div>

                <!-- Custom Progress Bar -->
                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; font-weight: 700; color: var(--color-scsa-muted); margin-bottom: 0.35rem;">
                        <span>Progress Metric</span>
                        <span>{{ round($percentage, 0) }}%</span>
                    </div>
                    <div style="width: 100%; height: 0.5rem; background-color: var(--color-scsa-line); border-radius: 999px; overflow: hidden; display: flex;">
                        <div style="width: {{ $percentage }}%; height: 100%; background-color: {{ $barColor }}; border-radius: 999px; transition: width 0.3s ease;"></div>
                    </div>
                </div>

                <!-- Breakdown Specifications -->
                <div style="background-color: var(--bg-primary); border-radius: var(--border-radius-lg); padding: 1rem; border: 1px solid var(--color-scsa-line);">
                    <div style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-scsa-muted); border-bottom: 1px dashed var(--color-scsa-line); padding-bottom: 0.5rem; margin-bottom: 0.75rem; display: flex; justify-content: space-between;">
                        <span>Evaluation Component</span>
                        <span>Marks Scored</span>
                    </div>
                    
                    <!-- Mid Sem -->
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8125rem; margin-bottom: 0.5rem;">
                        <span style="font-weight: 700; color: var(--color-scsa-ink);">Mid Sem Exam (Scaled to 20)</span>
                        <strong style="color: var(--color-scsa-ink);">{{ $mark->mid_sem_20 !== null ? $mark->mid_sem_20 : '0.00' }} <span style="font-weight: 500; font-size: 0.6875rem; color: var(--color-scsa-muted);">/ 20</span></strong>
                    </div>

                    <!-- Dynamic CIE values -->
                    @foreach($mark->componentValues as $val)
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8125rem; margin-bottom: 0.5rem;">
                            <span style="color: var(--color-scsa-muted); font-weight: 500;">{{ $val->component->name }}</span>
                            <strong style="color: var(--color-scsa-ink);">{{ $val->marks_obtained !== null ? $val->marks_obtained : 'N/A' }} <span style="font-weight: 500; font-size: 0.6875rem; color: var(--color-scsa-muted);">/ {{ (int)$val->component->max_marks }}</span></strong>
                        </div>
                    @endforeach

                    <!-- CIE Subtotal -->
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8125rem; border-top: 1px solid var(--color-scsa-line); padding-top: 0.5rem; margin-top: 0.5rem;">
                        <span style="font-weight: 700; color: var(--color-scsa-ink);">Continuous Internal Evaluation</span>
                        <strong style="color: var(--color-scsa-ink);">{{ $mark->cie_30 }} <span style="font-weight: 500; font-size: 0.6875rem; color: var(--color-scsa-muted);">/ 30</span></strong>
                    </div>
                </div>
            </div>
        @empty
            <div class="card" style="grid-column: span 2; text-align: center; padding: 4rem 1.5rem; border-radius: 1rem; border: 1px solid var(--color-scsa-line); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);">
                <span style="font-size: 3.5rem; display: block; margin-bottom: 1rem;">📭</span>
                <h2 style="margin-bottom: 0.5rem; font-weight: 700; color: var(--color-scsa-accent-deep); border: 0; padding-bottom: 0;">Gradesheet Awaiting Submission</h2>
                <p class="muted" style="max-width: 28rem; margin: 0 auto 1.5rem auto; font-size: 0.9375rem;">
                    Your subject faculty members have not submitted and finalized the internal marks sheets yet. Scorecard Not Available until approved by the HOD.
                </p>
            </div>
        @endforelse
    </div>
@endsection
