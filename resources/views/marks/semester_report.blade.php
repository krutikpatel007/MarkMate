@extends('layouts.app')

@section('title', 'Semester Grade Card | SCSA Attendance')
@section('page-title', 'Semester Grade Card')
@section('page-subtitle', 'Official academic performance statement')

@section('page-actions')
    <div class="actions" style="display: flex; gap: 0.5rem; align-items: center;">
        <button class="button" onclick="window.print();" style="background-color: var(--color-scsa-success); border-color: var(--color-scsa-success); padding: 0.55rem 1.25rem; font-size: 0.875rem;">
            🖨️ Print / Download PDF
        </button>
        <a class="button secondary" href="{{ auth()->user()->isStudent() ? route('marks.student') : route('academics.students.index') }}">
            Back
        </a>
    </div>
@endsection

@section('content')
    <div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 3rem; border-radius: var(--border-radius-xl); box-shadow: var(--shadow-md); border: 1px solid var(--color-scsa-line); position: relative; overflow: hidden;" class="grade-card-container">
        
        <!-- Watermark for provisional status -->
        @if(!$fullyDeclared)
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 3.5rem; font-weight: 900; color: rgba(239, 68, 68, 0.08); text-transform: uppercase; white-space: nowrap; pointer-events: none; user-select: none; z-index: 1; text-align: center; border: 6px double rgba(239, 68, 68, 0.08); padding: 1rem 2rem;">
                Provisional Transcript<br>Result Awaiting Declaration
            </div>
        @endif

        <!-- University Letterhead Header -->
        <div style="text-align: center; border-bottom: 2px double var(--color-scsa-line); padding-bottom: 1.5rem; margin-bottom: 2rem;">
            <img src="{{ asset('su_logo_horizontal.png') }}" alt="Shreyarth University Logo" style="max-height: 4.5rem; margin-bottom: 0.5rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: var(--color-scsa-muted);">
                Office of the Controller of Examinations
            </div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: #1e293b; margin: 0.5rem 0 0 0; text-transform: uppercase; border-bottom: 0; padding-bottom: 0;">
                Semester Grade Report
            </h2>
        </div>

        <!-- Student Info Matrix -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: var(--border-radius-lg); border: 1px solid var(--color-scsa-line);">
            <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.875rem;">
                <div><span class="muted" style="font-weight: 500; min-width: 120px; display: inline-block;">Student Name:</span> <strong style="color: #0f172a;">{{ $student->user->name }}</strong></div>
                <div><span class="muted" style="font-weight: 500; min-width: 120px; display: inline-block;">Enrollment No:</span> <strong style="color: #0f172a;">{{ $student->enrollment_no }}</strong></div>
                <div><span class="muted" style="font-weight: 500; min-width: 120px; display: inline-block;">Roll Number:</span> <strong style="color: #0f172a;">{{ $student->roll_no ?? '-' }}</strong></div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.875rem;">
                <div><span class="muted" style="font-weight: 500; min-width: 120px; display: inline-block;">Program Name:</span> <strong style="color: #0f172a;">{{ $student->program->program_name }}</strong></div>
                <div><span class="muted" style="font-weight: 500; min-width: 120px; display: inline-block;">Active Semester:</span> <strong style="color: #0f172a;">Semester {{ $student->semester->semester_no }}</strong></div>
                <div><span class="muted" style="font-weight: 500; min-width: 120px; display: inline-block;">Class Section:</span> <strong style="color: #0f172a;">{{ $student->classSection->section_name }}</strong></div>
            </div>
        </div>

        <!-- Subjects Grid Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 0.875rem;" class="transcript-table">
            <thead>
                <tr style="background: #0f172a; color: #fff;">
                    <th style="border: 1px solid #334155; padding: 0.75rem 1rem; text-align: left; font-weight: 600;">Code</th>
                    <th style="border: 1px solid #334155; padding: 0.75rem 1rem; text-align: left; font-weight: 600;">Subject Title</th>
                    <th style="border: 1px solid #334155; padding: 0.75rem 1rem; text-align: center; font-weight: 600;">Credits</th>
                    <th style="border: 1px solid #334155; padding: 0.75rem 1rem; text-align: center; font-weight: 600;">CIE (50)</th>
                    <th style="border: 1px solid #334155; padding: 0.75rem 1rem; text-align: center; font-weight: 600;">End Sem (50)</th>
                    <th style="border: 1px solid #334155; padding: 0.75rem 1rem; text-align: center; font-weight: 600;">Total (100)</th>
                    <th style="border: 1px solid #334155; padding: 0.75rem 1rem; text-align: center; font-weight: 600;">Grade</th>
                    <th style="border: 1px solid #334155; padding: 0.75rem 1rem; text-align: center; font-weight: 600;">GP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $row)
                    <tr>
                        <td style="border: 1px solid #cbd5e1; padding: 0.75rem 1rem; font-weight: 700; color: #334155;">{{ $row['subject_code'] }}</td>
                        <td style="border: 1px solid #cbd5e1; padding: 0.75rem 1rem; color: #0f172a; font-weight: 500;">{{ $row['subject_name'] }}</td>
                        <td style="border: 1px solid #cbd5e1; padding: 0.75rem 1rem; text-align: center; font-weight: 600;">{{ $row['credits'] }}</td>
                        <td style="border: 1px solid #cbd5e1; padding: 0.75rem 1rem; text-align: center;">{{ number_format($row['cie'], 2) }}</td>
                        <td style="border: 1px solid #cbd5e1; padding: 0.75rem 1rem; text-align: center;">
                            @if($row['external_submitted'])
                                {{ number_format($row['external'], 2) }}
                            @else
                                <span class="muted" style="font-style: italic; font-size: 0.8rem; color: var(--color-scsa-gold);">Awaiting</span>
                            @endif
                        </td>
                        <td style="border: 1px solid #cbd5e1; padding: 0.75rem 1rem; text-align: center; font-weight: 700; color: #0f172a;">
                            @if($row['external_submitted'])
                                {{ number_format($row['total'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="border: 1px solid #cbd5e1; padding: 0.75rem 1rem; text-align: center;">
                            @if($row['external_submitted'])
                                <span class="badge" style="background-color: {{ $row['grade'] === 'F' ? 'rgba(239, 68, 68, 0.12)' : 'rgba(16, 185, 129, 0.12)' }}; color: {{ $row['grade'] === 'F' ? 'var(--color-scsa-danger)' : 'var(--color-scsa-success)' }}; font-weight: 700;">
                                    {{ $row['grade'] }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td style="border: 1px solid #cbd5e1; padding: 0.75rem 1rem; text-align: center; font-weight: 600;">
                            @if($row['external_submitted'])
                                {{ $row['grade_point'] }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Result Summary Box -->
        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 2rem; margin-bottom: 2rem; align-items: start;">
            <div style="background: #f8fafc; border: 1px solid var(--color-scsa-line); border-radius: var(--border-radius-lg); padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                    <span class="muted" style="font-weight: 500;">Credits Registered:</span>
                    <strong style="color: #0f172a;">{{ $totalCredits }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                    <span class="muted" style="font-weight: 500;">Credits Earned:</span>
                    <strong style="color: #0f172a;">{{ $earnedCredits }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem; border-top: 1px solid var(--color-scsa-line); padding-top: 0.5rem; margin-top: 0.25rem;">
                    <span style="font-weight: 700; color: #0f172a;">Semester SGPA:</span>
                    <strong style="color: var(--color-scsa-success); font-size: 1.15rem; font-family: var(--font-display);">
                        {{ number_format($sgpa, 2) }}
                    </strong>
                </div>
            </div>

            <!-- Validation and stamp area -->
            <div style="font-size: 0.75rem; color: var(--color-scsa-muted); line-height: 1.5; display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                <div>
                    <strong>Verification & Integrity:</strong><br>
                    This is an electronically generated semester transcript fetched directly from the Shreyarth University marks server. Any corrections or disputes must be filed via the Re-Evaluation cell within 15 days of declaration.
                </div>
                <div style="margin-top: 0.75rem; font-family: monospace; background: #f1f5f9; padding: 0.5rem; border-radius: var(--border-radius-md); border: 1px solid #e2e8f0; word-break: break-all;">
                    SECURE-HASH: {{ strtoupper($validationHash) }}
                </div>
            </div>
        </div>

        <!-- Grading Scale & SGPA Rules Legend -->
        <div class="grading-legend" style="border-top: 1px dashed var(--color-scsa-line); padding-top: 1.25rem; margin-top: 1rem; margin-bottom: 2rem; font-size: 0.75rem; line-height: 1.4;">
            <h4 style="font-size: 0.8rem; font-weight: 700; color: #1e293b; margin: 0 0 0.5rem 0; border: 0; padding: 0; text-transform: uppercase; letter-spacing: 0.05em;">
                Grading Scale & SGPA Rules Reference
            </h4>
            <div class="grading-legend-grid" style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 1.5rem;">
                <!-- Grading Table -->
                <div>
                    <strong style="color: #475569; display: block; margin-bottom: 0.35rem;">Grading System:</strong>
                    <div class="grading-badges-container" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.25rem 0.5rem;">
                        <span style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid var(--color-scsa-line);">
                            <strong>O (Outstanding)</strong>
                            <span style="color: var(--color-scsa-success); font-weight: 700;">90-100% | 10 GP</span>
                        </span>
                        <span style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid var(--color-scsa-line);">
                            <strong>A+ (Excellent)</strong>
                            <span style="color: var(--color-scsa-success); font-weight: 700;">85-89% | 9 GP</span>
                        </span>
                        <span style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid var(--color-scsa-line);">
                            <strong>A (Very Good)</strong>
                            <span style="color: var(--color-scsa-success); font-weight: 700;">80-84% | 8 GP</span>
                        </span>
                        <span style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid var(--color-scsa-line);">
                            <strong>B+ (Good)</strong>
                            <span style="color: var(--color-scsa-success); font-weight: 700;">70-79% | 7 GP</span>
                        </span>
                        <span style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid var(--color-scsa-line);">
                            <strong>B (Above Avg)</strong>
                            <span style="color: var(--color-scsa-success); font-weight: 700;">60-69% | 6 GP</span>
                        </span>
                        <span style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid var(--color-scsa-line);">
                            <strong>C (Average)</strong>
                            <span style="color: var(--color-scsa-success); font-weight: 700;">50-59% | 5 GP</span>
                        </span>
                        <span style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid var(--color-scsa-line);">
                            <strong>P (Pass)</strong>
                            <span style="color: var(--color-scsa-success); font-weight: 700;">40-49% | 4 GP</span>
                        </span>
                        <span style="display: flex; align-items: center; justify-content: space-between; background: rgba(239, 68, 68, 0.05); padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid rgba(239, 68, 68, 0.15);">
                            <strong>F (Fail)</strong>
                            <span style="color: var(--color-scsa-danger); font-weight: 700;">&lt; 40% | 0 GP</span>
                        </span>
                    </div>
                </div>
                <!-- SGPA Rule -->
                <div>
                    <strong style="color: #475569; display: block; margin-bottom: 0.35rem;">SGPA Calculation Formula:</strong>
                    <div style="background: #f8fafc; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid var(--color-scsa-line); font-family: monospace; display: block; text-align: center; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                        SGPA = &Sigma;(C_i &times; GP_i) / &Sigma;(C_i)
                    </div>
                    <p style="font-size: 0.6875rem; color: var(--color-scsa-muted); margin: 0; line-height: 1.4;">
                        Where <strong>C_i</strong> represents the credit value of course <em>i</em> (range 1-6) and <strong>GP_i</strong> represents the Grade Point obtained in that course. SGPA calculations exclude courses awaiting declaration.
                    </p>
                </div>
            </div>
        </div>

        <!-- Official Signatures -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; padding-top: 1.5rem;">
            <div style="text-align: center;">
                <div style="width: 140px; height: 50px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-style: italic; color: #94a3b8; border-bottom: 1px solid #94a3b8; margin-bottom: 0.5rem; margin-left: auto; margin-right: auto;">
                    University Stamp
                </div>
                <div style="font-size: 0.8rem; font-weight: 700; color: #475569;">OFFICIAL SEAL</div>
            </div>
            <div style="text-align: center;">
                <!-- Controller Signature Placeholder -->
                <div style="font-family: 'Outfit', cursive; font-size: 1.25rem; font-weight: 600; color: #334155; margin-bottom: 0.25rem;">
                    Dr. K. C. Vyas
                </div>
                <div style="width: 180px; border-bottom: 1px solid #475569; margin-bottom: 0.5rem; margin-left: auto; margin-right: auto;"></div>
                <div style="font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">Controller of Examinations</div>
            </div>
        </div>

    </div>

    <!-- Print Stylesheet rules -->
    <style>
        @media print {
            /* Hide sidebar, headers, and print actions buttons completely */
            nav, header, footer, .actions, .page-header, .sidebar, #sidebar {
                display: none !important;
            }
            body, .content, main {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .grade-card-container {
                border: 0 !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 auto !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            /* A4 scaling rules */
            @page {
                size: A4;
                margin: 1.5cm;
            }
            .grading-legend {
                margin-top: 0.75rem !important;
                margin-bottom: 1rem !important;
                padding-top: 0.75rem !important;
            }
            .grading-legend h4 {
                font-size: 0.7rem !important;
                margin-bottom: 0.25rem !important;
            }
            .grading-legend-grid {
                gap: 0.75rem !important;
            }
            .grading-badges-container span {
                padding: 0.15rem 0.35rem !important;
                font-size: 0.65rem !important;
            }
        }
    </style>
@endsection
