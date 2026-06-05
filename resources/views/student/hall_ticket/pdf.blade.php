<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Hall Ticket - {{ $student->enrollment_no }}</title>
    <style>
        @page {
            size: A4;
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1a1a1a;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            font-size: 12px;
            background: #fff;
        }
        .container {
            border: 1px solid #c0c0c0;
            padding: 20px;
            border-radius: 6px;
            position: relative;
            background: #fff;
            min-height: 25.5cm;
            box-sizing: border-box;
        }
        .header {
            display: flex;
            align-items: center;
            border-bottom: 3px double #0d9488;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .logo-wrap {
            flex: 0 0 160px;
        }
        .logo-img {
            max-width: 100%;
            height: auto;
            max-height: 60px;
            object-fit: contain;
        }
        .header-text {
            flex: 1;
            text-align: right;
        }
        .header-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .header-subtitle {
            font-size: 10px;
            font-weight: 700;
            color: #0d9488;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 4px;
            margin-bottom: 0;
        }
        .title-block {
            text-align: center;
            margin-bottom: 20px;
        }
        .main-title {
            font-size: 15px;
            font-weight: 800;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
            display: inline-block;
            border-bottom: 2px solid #000;
            padding-bottom: 2px;
        }
        .profile-section {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        .profile-table {
            flex: 1;
            border-collapse: collapse;
            width: 100%;
        }
        .profile-table td {
            padding: 6px 8px;
            vertical-align: top;
        }
        .profile-table td.label {
            font-weight: 600;
            color: #475569;
            width: 30%;
        }
        .profile-table td.value {
            font-weight: 700;
            color: #0f172a;
        }
        .photo-box {
            flex: 0 0 100px;
            height: 120px;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 9px;
            color: #64748b;
            background: #f8fafc;
            box-sizing: border-box;
            padding: 5px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .subjects-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .subjects-table th, .subjects-table td {
            border: 1px solid #94a3b8;
            padding: 8px 10px;
            text-align: left;
        }
        .subjects-table th {
            background-color: #f1f5f9;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            font-size: 10px;
        }
        .subjects-table td {
            font-size: 11px;
        }
        .subjects-table td.placeholder {
            color: #94a3b8;
            font-style: italic;
            font-size: 10px;
        }
        .instructions {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        .instructions h4 {
            margin: 0 0 8px 0;
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
        }
        .instructions ol {
            margin: 0;
            padding-left: 20px;
            font-size: 10px;
            color: #475569;
        }
        .instructions li {
            margin-bottom: 6px;
            line-height: 1.5;
        }
        .footer-signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .signature-block {
            text-align: center;
            width: 200px;
        }
        .sig-line {
            border-top: 1px solid #334155;
            margin-top: 40px;
            padding-top: 6px;
            font-weight: 700;
            font-size: 11px;
            color: #1e293b;
            text-transform: uppercase;
        }
        .stamp-watermark {
            position: absolute;
            bottom: 10%;
            left: 50%;
            transform: translate(-50%, 0);
            width: 300px;
            height: auto;
            opacity: 0.04;
            pointer-events: none;
        }
        .verification-code {
            text-align: center;
            font-family: monospace;
            font-size: 8px;
            color: #94a3b8;
            margin-top: 40px;
            letter-spacing: 1px;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <!-- Stamp Background Watermark -->
        <img src="{{ asset('su_logo_horizontal.png') }}" class="stamp-watermark" alt="Watermark">

        <div class="header">
            <div class="logo-wrap">
                <img src="{{ asset('su_logo_horizontal.png') }}" class="logo-img" alt="Shreyarth University Logo">
            </div>
            <div class="header-text">
                <h1 class="header-title">Shreyarth University</h1>
                <div class="header-subtitle">Central Examination Cell</div>
            </div>
        </div>

        <div class="title-block">
            <h2 class="main-title">End-Semester Examinations Hall Ticket</h2>
        </div>

        <div class="profile-section">
            <table class="profile-table">
                <tr>
                    <td class="label">Candidate Name:</td>
                    <td class="value">{{ $student->user->name }}</td>
                </tr>
                <tr>
                    <td class="label">Enrollment No:</td>
                    <td class="value">{{ $student->enrollment_no }}</td>
                </tr>
                <tr>
                    <td class="label">Roll Number:</td>
                    <td class="value">{{ $student->roll_no }}</td>
                </tr>
                <tr>
                    <td class="label">Program of Study:</td>
                    <td class="value">{{ $student->program->program_name }}</td>
                </tr>
                <tr>
                    <td class="label">Semester / Section:</td>
                    <td class="value">Semester {{ $student->semester->semester_no }} | Section {{ $student->classSection->section_name }}</td>
                </tr>
                <tr>
                    <td class="label">Waiver Override:</td>
                    <td class="value">
                        @if($hasWaiver)
                            <span style="color: #047857;">GRANTED ({{ $student->examWaiver->reason }})</span>
                        @else
                            <span>NORMAL CLEARANCE</span>
                        @endif
                    </td>
                </tr>
            </table>
            <div class="photo-box">
                Affix Recent<br>Passport Size<br>Photograph
            </div>
        </div>

        <table class="subjects-table">
            <thead>
            <tr>
                <th style="width: 15%;">Subject Code</th>
                <th style="width: 35%;">Subject Name</th>
                <th style="width: 20%;">Exam Date &amp; Time</th>
                <th style="width: 15%;">Room / Block</th>
                <th style="width: 15%;">Invigilator Sign</th>
            </tr>
            </thead>
            <tbody>
            @foreach($subjects as $assignment)
                <tr>
                    <td><strong>{{ $assignment->subject->subject_code }}</strong></td>
                    <td>{{ $assignment->subject->subject_name }}</td>
                    <td class="placeholder">Printed in Calendar</td>
                    <td class="placeholder">Block Allocation</td>
                    <td></td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="instructions">
            <h4>Instructions to the Candidate</h4>
            <ol>
                <li>Candidates must produce this official Hall Ticket along with their University Photo Identity Card to gain entry into the examination block.</li>
                <li>Possession of mobile phones, smartwatches, calculators, or any unauthorized paper sheets inside the examination block is strictly prohibited and constitutes unfair practice.</li>
                <li>Candidates should report to the allocated examination block at least 20 minutes prior to the commencement of the scheduled session.</li>
                <li>No candidate will be allowed to enter the exam block 30 minutes after the exam has started, or leave before 45 minutes have elapsed.</li>
                <li>The Hall Ticket is valid only if all attendance eligibility thresholds are satisfied or a Coordinator waiver has been active.</li>
            </ol>
        </div>

        <div class="footer-signatures">
            <div class="signature-block">
                <div class="sig-line">Candidate Signature</div>
            </div>
            <div class="signature-block">
                <!-- Mock QR Code Box -->
                <div style="width: 70px; height: 70px; border: 1px solid #000; margin: 0 auto 5px auto; display: flex; align-items: center; justify-content: center; font-size: 7px; text-align: center; background: #fafafa;">
                    [SECURE<br>QR HASH]
                </div>
                <div style="font-size: 8px; font-weight: 700; color: #475569;">DIGITAL VERIFICATION</div>
            </div>
            <div class="signature-block">
                <div class="sig-line">Controller of Exams</div>
            </div>
        </div>

        <div class="verification-code">
            VERIFICATION HASH: MD5-{{ md5($student->enrollment_no . now()->toDateString()) }}-SHA256-{{ hash('sha256', $student->id . 'CLEARED') }}
        </div>
    </div>
</body>
</html>
