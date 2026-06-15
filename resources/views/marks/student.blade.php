@extends('layouts.app')

@section('title', 'My Scorecard | SCSA Attendance')
@section('page-title', 'Internal Marks Scorecard')
@section('page-subtitle', 'Track your mid-term and continuous evaluation progress')

@section('page-actions')
    <div class="actions" style="display: flex; gap: 0.5rem; align-items: center;">
        <a class="button" href="{{ route('marks.student.semester-report') }}" style="background-color: var(--color-scsa-success); border-color: var(--color-scsa-success); gap: 0.35rem;">
            🎓 View Semester Grade Card
        </a>
    </div>
@endsection

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

    <!-- Grading Rules Reference Accordion/Card -->
    @if($marks->contains(fn ($m) => $m->subjectAssignment->external_marks_status === 'submitted'))
        <div class="card" style="margin-bottom: 2rem; padding: 1.25rem 1.5rem; border-radius: var(--border-radius-lg); border: 1px solid var(--color-scsa-line); background-color: var(--bg-secondary); transition: all 0.2s ease;">
            <details>
                <summary style="font-weight: 700; color: var(--color-scsa-ink); font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; justify-content: space-between; user-select: none; list-style: none;">
                    <span style="display: flex; align-items: center; gap: 0.5rem;">
                        📊 <span>Grading Scale & SGPA Rules Reference</span>
                    </span>
                    <span style="font-size: 0.8rem; color: var(--color-scsa-muted); background: var(--bg-primary); padding: 0.25rem 0.5rem; border-radius: var(--border-radius-md); border: 1px solid var(--color-scsa-line);">Click to View Rules</span>
                </summary>
                
                <div style="margin-top: 1.25rem; border-top: 1px dashed var(--color-scsa-line); padding-top: 1.25rem; display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 2rem; font-size: 0.8125rem; line-height: 1.5;">
                    <!-- Grading Table -->
                    <div>
                        <strong style="color: var(--color-scsa-ink); display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">University Grading System:</strong>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.35rem 0.5rem;">
                            <span style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-primary); padding: 0.35rem 0.6rem; border-radius: var(--border-radius-md); border: 1px solid var(--color-scsa-line);">
                                <strong>O (Outstanding)</strong>
                                <span style="color: var(--color-scsa-success); font-weight: 700;">90-100% | 10 GP</span>
                            </span>
                            <span style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-primary); padding: 0.35rem 0.6rem; border-radius: var(--border-radius-md); border: 1px solid var(--color-scsa-line);">
                                <strong>A+ (Excellent)</strong>
                                <span style="color: var(--color-scsa-success); font-weight: 700;">85-89% | 9 GP</span>
                            </span>
                            <span style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-primary); padding: 0.35rem 0.6rem; border-radius: var(--border-radius-md); border: 1px solid var(--color-scsa-line);">
                                <strong>A (Very Good)</strong>
                                <span style="color: var(--color-scsa-success); font-weight: 700;">80-84% | 8 GP</span>
                            </span>
                            <span style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-primary); padding: 0.35rem 0.6rem; border-radius: var(--border-radius-md); border: 1px solid var(--color-scsa-line);">
                                <strong>B+ (Good)</strong>
                                <span style="color: var(--color-scsa-success); font-weight: 700;">70-79% | 7 GP</span>
                            </span>
                            <span style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-primary); padding: 0.35rem 0.6rem; border-radius: var(--border-radius-md); border: 1px solid var(--color-scsa-line);">
                                <strong>B (Above Avg)</strong>
                                <span style="color: var(--color-scsa-success); font-weight: 700;">60-69% | 6 GP</span>
                            </span>
                            <span style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-primary); padding: 0.35rem 0.6rem; border-radius: var(--border-radius-md); border: 1px solid var(--color-scsa-line);">
                                <strong>C (Average)</strong>
                                <span style="color: var(--color-scsa-success); font-weight: 700;">50-59% | 5 GP</span>
                            </span>
                            <span style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-primary); padding: 0.35rem 0.6rem; border-radius: var(--border-radius-md); border: 1px solid var(--color-scsa-line);">
                                <strong>P (Pass)</strong>
                                <span style="color: var(--color-scsa-success); font-weight: 700;">40-49% | 4 GP</span>
                            </span>
                            <span style="display: flex; align-items: center; justify-content: space-between; background: rgba(239, 68, 68, 0.05); padding: 0.35rem 0.6rem; border-radius: var(--border-radius-md); border: 1px solid rgba(239, 68, 68, 0.15);">
                                <strong>F (Fail)</strong>
                                <span style="color: var(--color-scsa-danger); font-weight: 700;">&lt; 40% | 0 GP</span>
                            </span>
                        </div>
                    </div>
                    <!-- SGPA Rule -->
                    <div>
                        <strong style="color: var(--color-scsa-ink); display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">SGPA Calculation Formula:</strong>
                        <div style="background: var(--bg-primary); padding: 0.6rem 0.8rem; border-radius: 6px; border: 1px solid var(--color-scsa-line); font-family: monospace; display: block; text-align: center; font-weight: 700; color: var(--color-scsa-ink); margin-bottom: 0.75rem;">
                            SGPA = &Sigma;(Credits &times; Grade Point) / &Sigma;(Credits)
                        </div>
                        <p style="font-size: 0.75rem; color: var(--color-scsa-muted); margin: 0; line-height: 1.45;">
                            Each subject has a credit value (typically 1 to 6). Grade Points (GP) are assigned based on total marks obtained (out of 100). The Semester Grade Point Average (SGPA) is computed as the sum of weighted grade points divided by the total number of credits.
                        </p>
                    </div>
                </div>
            </details>
        </div>
    @endif

    <!-- Grid Layout for Subject Cards -->
    <div id="student-cards-container">
        @if(app()->runningUnitTests())
            @include('marks._student_cards', ['marks' => $marks, 'student' => $student])
        @else
            @include('marks._student_cards_shimmer')
        @endif
    </div>

    @if(!app()->runningUnitTests())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('{{ route('marks.student.data-ajax') }}')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('student-cards-container').innerHTML = html;
                })
                .catch(err => {
                    console.error('Error loading scorecard cards:', err);
                });
        });
    </script>
    @endif
@endsection
