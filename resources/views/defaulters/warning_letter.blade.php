<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Warning Letter | {{ $student->user->name }}</title>
    <style>
        :root {
            --color-text: #1e293b;
            --color-text-muted: #64748b;
            --color-navy: #0f172a;
            --color-gold: #b45309;
            --color-border: #cbd5e1;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            color: var(--color-text);
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
        }

        /* Toolbar styles */
        .print-toolbar {
            background-color: var(--color-navy);
            padding: 0.75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .toolbar-title {
            color: #ffffff;
            font-family: system-ui, sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .toolbar-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            font-family: system-ui, sans-serif;
            font-size: 0.8125rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-primary {
            background-color: #f59e0b;
            color: #0f172a;
        }

        .btn-secondary {
            background-color: #334155;
            color: #ffffff;
        }

        /* Letter container styles */
        .letter-container {
            background-color: #ffffff;
            width: 21cm;
            min-height: 29.7cm;
            margin: 2rem auto;
            padding: 2.5cm 2cm;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            box-sizing: border-box;
            position: relative;
        }

        /* Official Letterhead */
        .letterhead {
            text-align: center;
            border-bottom: 2px double var(--color-navy);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .letterhead-uni {
            font-size: 1.6rem;
            font-weight: bold;
            color: var(--color-navy);
            letter-spacing: 0.05em;
            margin: 0;
            text-transform: uppercase;
        }

        .letterhead-dept {
            font-size: 1.05rem;
            font-weight: bold;
            color: var(--color-gold);
            margin: 0.25rem 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .letterhead-contact {
            font-size: 0.75rem;
            color: var(--color-text-muted);
            margin: 0.35rem 0 0 0;
        }

        /* Document info */
        .doc-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .warning-title {
            text-align: center;
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--color-navy);
            text-decoration: underline;
            margin: 1.5rem 0;
            letter-spacing: 0.02em;
        }

        /* Letter content */
        .address-block {
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .salutation {
            margin-bottom: 1rem;
            font-size: 0.95rem;
            font-weight: bold;
        }

        .letter-body {
            font-size: 0.95rem;
            text-align: justify;
            margin-bottom: 1.5rem;
        }

        /* Subject table */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            font-size: 0.9rem;
        }

        .stats-table th, .stats-table td {
            border: 1px solid var(--color-border);
            padding: 0.6rem 0.8rem;
            text-align: left;
        }

        .stats-table th {
            background-color: #f8fafc;
            color: var(--color-navy);
            font-weight: bold;
        }

        .stats-table tr.total-row {
            font-weight: bold;
            background-color: #f1f5f9;
        }

        /* Warning note */
        .critical-note {
            border-left: 4px solid var(--color-gold);
            background-color: #fef3c7;
            padding: 0.75rem 1rem;
            margin: 1.5rem 0;
            font-size: 0.925rem;
            font-weight: bold;
        }

        /* Signatures block */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 3.5rem;
            font-size: 0.95rem;
        }

        .signature-line {
            width: 4.5rem;
            border-top: 1px solid var(--color-text);
            margin-bottom: 0.25rem;
        }

        .signature-box {
            text-align: center;
            width: 30%;
        }

        /* Print Media Override */
        @media print {
            body {
                background-color: #ffffff;
                font-size: 11pt !important;
            }

            .print-toolbar {
                display: none !important;
            }

            .letter-container {
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                width: 100% !important;
                min-height: auto !important;
            }

            @page {
                size: A4;
                margin: 1.2cm 1.2cm;
            }

            /* Visual compression overrides to keep all content on one A4 sheet */
            .letterhead {
                margin-bottom: 1rem !important;
                padding-bottom: 0.5rem !important;
            }

            .warning-title {
                margin: 0.75rem 0 !important;
                font-size: 1.15rem !important;
            }

            .address-block,
            .letter-body {
                margin-bottom: 0.75rem !important;
            }

            .stats-table {
                margin: 0.75rem 0 !important;
            }

            .stats-table th, .stats-table td {
                padding: 0.45rem 0.6rem !important;
            }

            .critical-note {
                margin: 0.75rem 0 !important;
                padding: 0.5rem 0.75rem !important;
            }

            .signatures {
                margin-top: 1.75rem !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
@php
    $deptCode = strtoupper($student->program->department->department_code ?? '');
    $contactEmail = ($deptCode === 'SOM') ? 'krutpal@shreyarthuni.ac.in' : 'nikita@shreyarthuni.ac.in';
@endphp

    <!-- Print control toolbar -->
    <div class="print-toolbar">
        <div class="toolbar-title">Attendance Management System Defaulter Warning Letter</div>
        <div class="toolbar-actions">
            <button class="btn btn-primary" onclick="window.print()">Print Warning Letter</button>
            <button class="btn btn-secondary" onclick="window.close()">Close Window</button>
        </div>
    </div>

    <!-- Letter template -->
    <div class="letter-container">
        <!-- Letterhead -->
        <div class="letterhead" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.35rem; border-bottom: 2px double var(--color-navy); padding-bottom: 0.75rem; margin-bottom: 1.5rem;">
            <img src="{{ asset('su_logo_horizontal.png') }}" alt="Shreyarth University Logo" style="max-height: 4.25rem; width: auto; object-fit: contain; margin-bottom: 0.25rem;">
            <h2 class="letterhead-dept" style="margin: 0; font-size: 1.15rem; font-weight: bold; color: var(--color-gold);">{{ $student->program->department->department_name }}</h2>
            <div class="letterhead-contact" style="margin-top: 0.15rem;">Gujarat Bhavan Nr. M. J. Library, Ashram Rd, Ellisbridge, Ahmedabad, Gujarat 380009 | email: {{ $contactEmail }}</div>
        </div>

        <!-- Meta info -->
        <div class="doc-meta">
            <div>Ref No: SU/{{ $student->program->program_code }}/ATT-WRN/{{ date('Y') }}/{{ str_pad($student->id, 4, '0', STR_PAD_LEFT) }}</div>
            <div>Date: {{ date('F d, Y') }}</div>
        </div>

        <div class="address-block">
            <strong>To,</strong><br>
            The Parents/Guardians of {{ $student->user->name }},<br>
            Enrollment Number: {{ $student->enrollment_no }} | Roll No: {{ $student->roll_no ?? 'N/A' }},<br>
            Class Section: {{ $student->classSection->display_name }},<br>
            Program: {{ $student->program->program_name }} Sem {{ $student->semester->semester_no }}.
        </div>

        <div class="warning-title">OFFICIAL ATTENDANCE WARNING NOTIFICATION</div>

        <div class="salutation">Dear Parent / Guardian,</div>

        <div class="letter-body">
            This letter is to formally bring to your attention that your student, <strong>{{ $student->user->name }}</strong>, has failed to maintain the mandatory 75% attendance threshold required by Shreyarth University regulations.
        </div>

        <div class="letter-body">
            According to our current records, the overall cumulative attendance of your student stands at <strong>{{ $overallPercentage }}%</strong>. The subject-wise attendance breakdown is provided below for your detailed review:
        </div>

        <!-- Attendance Stats Table -->
        <table class="stats-table">
            <thead>
            <tr>
                <th>Subject Name (Code)</th>
                <th style="text-align: center;">Conducted Lectures</th>
                <th style="text-align: center;">Attended Lectures</th>
                <th style="text-align: center;">Attendance Percentage</th>
            </tr>
            </thead>
            <tbody>
            @foreach($subjectStats as $row)
                <tr>
                    <td>{{ $row->subject_name }} ({{ $row->subject_code }})</td>
                    <td style="text-align: center;">{{ $row->conducted_count }}</td>
                    <td style="text-align: center;">{{ $row->present_count }}</td>
                    <td style="text-align: center; font-weight: bold; color: {{ $row->percentage < 75.0 ? 'var(--color-gold)' : 'inherit' }};">
                        {{ $row->percentage }}%
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Overall Cumulative Total</td>
                <td style="text-align: center;">{{ $overallConducted }}</td>
                <td style="text-align: center;">{{ $overallPresent }}</td>
                <td style="text-align: center; font-size: 1.05rem; color: {{ $overallPercentage < 75.0 ? 'var(--color-gold)' : 'inherit' }};">
                    {{ $overallPercentage }}%
                </td>
            </tr>
            </tbody>
        </table>

        <!-- Critical compliance text -->
        <div class="critical-note">
            CRITICAL NOTICE: According to Shreyarth University Academic Regulations, a minimum of 75% cumulative attendance is mandatory to qualify for the final semester examinations. If the student fails to reconcile this gap immediately, they will be disqualified and ineligible to sit for the term-end exams.
        </div>

        <div class="letter-body">
            We highly request you to contact the department advisor or call our office immediately to discuss remedial steps and ensure your student attends all future scheduled lectures without failure.
        </div>

        <div class="letter-body" style="margin-top: 1rem;">
            Sincerely yours,
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line" style="margin: 0 auto 0.25rem auto;"></div>
                <strong>Class Advisor</strong><br>
                {{ $student->program->program_code }} Department
            </div>
            <div class="signature-box">
                <div class="signature-line" style="margin: 0 auto 0.25rem auto;"></div>
                <strong>Head of Department</strong><br>
                {{ $student->program->department->department_code }}
            </div>
            <div class="signature-box">
                <div class="signature-line" style="margin: 0 auto 0.25rem auto;"></div>
                <strong>Parent Signature</strong><br>
                Date: ____/____/______
            </div>
        </div>
    </div>

</body>
</html>
