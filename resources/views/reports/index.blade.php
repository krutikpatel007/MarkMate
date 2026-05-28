@extends('layouts.app')

@section('title', 'Reports | SCSA Attendance')
@section('page-title', 'Reports')
@section('page-subtitle', 'Student-wise, class-wise, daily, defaulter, and faculty lecture overview')

@section('content')
    <div class="grid grid-2">
        <section class="card" id="defaulters">
            <h2>Student-wise Attendance</h2>
            <table>
                <thead>
                <tr>
                    <th>Student</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Leave</th>
                    <th>%</th>
                </tr>
                </thead>
                <tbody>
                @forelse($studentSummaries as $row)
                    <tr>
                        <td>{{ $row->name }} <div class="muted">{{ $row->enrollment_no }}</div></td>
                        <td>{{ $row->present_count }}</td>
                        <td>{{ $row->absent_count }}</td>
                        <td>{{ $row->leave_count }}</td>
                        <td><span class="badge {{ $row->percentage < 75 ? 'danger' : 'success' }}">{{ $row->percentage }}%</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No attendance submitted yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <section class="card" id="low-attendance-classes">
            <h2>Defaulter List Below 75%</h2>
            <table>
                <thead>
                <tr>
                    <th>Student</th>
                    <th>Attendance</th>
                </tr>
                </thead>
                <tbody>
                @forelse($defaulters as $row)
                    <tr>
                        <td>{{ $row->name }} <div class="muted">{{ $row->enrollment_no }}</div></td>
                        <td><span class="badge danger">{{ $row->percentage }}%</span></td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="muted">No students are below 75%.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2>Class-wise Report</h2>
            <form method="get" action="{{ route('reports.class-attendance.export') }}" style="margin-bottom: 1rem;">
                <div class="field">
                    <label for="class_section_id">Class</label>
                    <select id="class_section_id" name="class_section_id" required>
                        <option value="">Select class</option>
                        @foreach($classSections as $section)
                            <option value="{{ $section->id }}">{{ $section->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-2">
                    <div class="field">
                        <label for="from_date">From date</label>
                        <input id="from_date" name="from_date" type="date">
                    </div>

                    <div class="field">
                        <label for="to_date">To date</label>
                        <input id="to_date" name="to_date" type="date">
                    </div>
                </div>

                <button class="button" type="submit">Export CSV</button>
            </form>

            <table>
                <thead>
                <tr>
                    <th>Class</th>
                    <th>Students</th>
                </tr>
                </thead>
                <tbody>
                @foreach($classSections as $section)
                    <tr>
                        <td>{{ $section->display_name }}</td>
                        <td>{{ $section->students_count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2>Daily Attendance</h2>
            <table>
                <thead>
                <tr>
                    <th>Lecture</th>
                    <th>Faculty</th>
                    <th>Status</th>
                    <th>Marked</th>
                </tr>
                </thead>
                <tbody>
                @foreach($dailySessions as $session)
                    <tr>
                        <td>
                            {{ $session->subjectAssignment->subject->subject_name }}
                            <div class="muted">{{ substr($session->start_time, 0, 5) }} | {{ $session->subjectAssignment->classSection->display_name }}</div>
                        </td>
                        <td>{{ $session->subjectAssignment->faculty->user->name }}</td>
                        <td><span class="badge">{{ $session->status }}</span></td>
                        <td>{{ $session->attendanceRecords->count() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2>Faculty Lecture Report</h2>
            <table>
                <thead>
                <tr>
                    <th>Faculty</th>
                    <th>Subjects taught</th>
                </tr>
                </thead>
                <tbody>
                @foreach($faculty as $member)
                    <tr>
                        <td>{{ $member->user->name }}</td>
                        <td>
                            @forelse($member->subjectAssignments->unique('subject_id') as $row)
                                <span class="badge">{{ $row->subject->subject_code }}</span>
                            @empty
                                <span class="muted">-</span>
                            @endforelse
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2>Subject-wise Report</h2>
            <form method="get" action="{{ route('reports.subject-attendance.export') }}" style="margin-bottom: 1rem;">
                <div class="field">
                    <label for="report_subject_assignment_id">Subject</label>
                    <select id="report_subject_assignment_id" name="subject_assignment_id" required>
                        <option value="">Select subject</option>
                        @foreach($subjectAssignments as $assignment)
                            <option value="{{ $assignment->id }}">
                                {{ $assignment->classSection->display_name }} | {{ $assignment->subject->subject_code }} - {{ $assignment->subject->subject_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-2">
                    <div class="field">
                        <label for="subject_academic_term">Academic term</label>
                        <input id="subject_academic_term" name="academic_term" placeholder="Odd 2025">
                    </div>
                    <div class="field">
                        <label for="subject_from_date">From date</label>
                        <input id="subject_from_date" name="from_date" type="date">
                    </div>
                    <div class="field">
                        <label for="subject_to_date">To date</label>
                        <input id="subject_to_date" name="to_date" type="date">
                    </div>
                </div>

                <button class="button" type="submit">Export Subject CSV</button>
            </form>
            <p>Total active student records: <strong>{{ $totalStudents }}</strong></p>
        </section>
    </div>
@endsection
