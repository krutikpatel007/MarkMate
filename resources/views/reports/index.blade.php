@extends('layouts.app')

@section('title', 'Reports | SCSA Attendance')
@section('page-title', 'Reports')
@section('page-subtitle', 'Student-wise, class-wise, daily, defaulter, and faculty lecture overview')

@section('content')
    <section class="card" style="margin-bottom: 1.5rem; padding: 1.25rem 1.5rem;">
        <form method="get" action="{{ route('reports.index') }}" style="margin: 0;">
            <div class="grid grid-4" style="align-items: flex-end; gap: 1.25rem;">
                <div class="field" style="margin-bottom: 0;">
                    <label for="filter_semester_id" style="font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-scsa-muted);">Semester</label>
                    <select id="filter_semester_id" name="semester_id" style="width: 100%;">
                        <option value="">All Semesters</option>
                        @foreach($semesters as $sem)
                            <option value="{{ $sem->id }}" {{ request('semester_id') == $sem->id ? 'selected' : '' }}>
                                {{ $sem->program->program_code }} Sem {{ $sem->semester_no }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field" style="margin-bottom: 0;">
                    <label for="filter_class_section_id" style="font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-scsa-muted);">Class Section</label>
                    <select id="filter_class_section_id" name="class_section_id" style="width: 100%;">
                        <option value="">All Classes</option>
                        @foreach($classSections as $section)
                            <option value="{{ $section->id }}" data-semester-id="{{ $section->semester_id }}" {{ request('class_section_id') == $section->id ? 'selected' : '' }}>
                                {{ $section->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field" style="margin-bottom: 0;">
                    <label for="filter_subject_id" style="font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-scsa-muted);">Subject</label>
                    <select id="filter_subject_id" name="subject_id" style="width: 100%;">
                        <option value="">All Subjects</option>
                        @foreach($subjects as $sub)
                            <option value="{{ $sub->id }}" data-semester-id="{{ $sub->semester_id }}" {{ request('subject_id') == $sub->id ? 'selected' : '' }}>
                                {{ $sub->program->program_code }} | {{ $sub->subject_code }} - {{ $sub->subject_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; width: 100%;">
                    <button class="button" type="submit" style="flex: 1; min-height: unset; padding: 0.55rem 1rem;">Filter</button>
                    <a href="{{ route('reports.index') }}" class="button secondary" style="flex: 1; text-align: center; text-decoration: none; min-height: unset; padding: 0.55rem 1rem; display: inline-flex; align-items: center; justify-content: center;">Clear</a>
                </div>
            </div>
        </form>
    </section>

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

                <div class="grid grid-3">
                    <div class="field">
                        <label for="from_date">From date</label>
                        <input id="from_date" name="from_date" type="date">
                    </div>

                    <div class="field">
                        <label for="to_date">To date</label>
                        <input id="to_date" name="to_date" type="date">
                    </div>

                    <div class="field">
                        <label for="class_session_type">Session Type</label>
                        <select id="class_session_type" name="session_type">
                            <option value="">All (Lecture & Lab)</option>
                            <option value="regular">Lecture Only</option>
                            <option value="lab">Lab Only</option>
                        </select>
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

                <div class="grid grid-4">
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
                    <div class="field">
                        <label for="subject_session_type">Session Type</label>
                        <select id="subject_session_type" name="session_type">
                            <option value="">All (Lecture & Lab)</option>
                            <option value="regular">Lecture Only</option>
                            <option value="lab">Lab Only</option>
                        </select>
                    </div>
                </div>

                <button class="button" type="submit">Export Subject CSV</button>
            </form>
            <p>Total active student records: <strong>{{ $totalStudents }}</strong></p>
        </section>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const semesterSelect = document.getElementById('filter_semester_id');
        const classSelect = document.getElementById('filter_class_section_id');
        const subjectSelect = document.getElementById('filter_subject_id');

        if (!semesterSelect || !classSelect || !subjectSelect) return;

        // Keep copies of original options
        const classOptions = Array.from(classSelect.options);
        const subjectOptions = Array.from(subjectSelect.options);

        function updateFilters() {
            const selectedSemesterId = semesterSelect.value;
            const selectedClassOption = classSelect.options[classSelect.selectedIndex];
            const classSemesterId = selectedClassOption ? selectedClassOption.getAttribute('data-semester-id') : null;

            // Determine active semester filter
            let activeSemesterId = selectedSemesterId;
            if (!activeSemesterId && classSemesterId) {
                activeSemesterId = classSemesterId;
            }

            // Filter Class Sections based on selected Semester
            if (selectedSemesterId) {
                const currentClassVal = classSelect.value;
                classSelect.innerHTML = '';
                classOptions.forEach(opt => {
                    if (!opt.value || opt.getAttribute('data-semester-id') === selectedSemesterId) {
                        classSelect.appendChild(opt);
                    }
                });
                // Keep the selected value if it's still present in the filtered list
                const classOptionExists = Array.from(classSelect.options).some(o => o.value === currentClassVal);
                classSelect.value = classOptionExists ? currentClassVal : '';
            } else {
                // Restore all class options
                const currentClassVal = classSelect.value;
                classSelect.innerHTML = '';
                classOptions.forEach(opt => classSelect.appendChild(opt));
                classSelect.value = currentClassVal;
            }

            // Filter Subjects based on active Semester
            if (activeSemesterId) {
                const currentSubVal = subjectSelect.value;
                subjectSelect.innerHTML = '';
                subjectOptions.forEach(opt => {
                    if (!opt.value || opt.getAttribute('data-semester-id') === activeSemesterId) {
                        subjectSelect.appendChild(opt);
                    }
                });
                // Re-select subject if it matches, else clear
                const selectedSubOptionExists = Array.from(subjectSelect.options).some(o => o.value === currentSubVal);
                subjectSelect.value = selectedSubOptionExists ? currentSubVal : '';
            } else {
                // Restore all subject options
                const currentSubVal = subjectSelect.value;
                subjectSelect.innerHTML = '';
                subjectOptions.forEach(opt => subjectSelect.appendChild(opt));
                subjectSelect.value = currentSubVal;
            }
        }

        semesterSelect.addEventListener('change', function() {
            // If they change semester, clear class selection if it doesn't match the new semester
            if (semesterSelect.value && classSelect.value) {
                const selectedClassOption = classSelect.options[classSelect.selectedIndex];
                if (selectedClassOption && selectedClassOption.getAttribute('data-semester-id') !== semesterSelect.value) {
                    classSelect.value = '';
                }
            }
            updateFilters();
        });
        
        classSelect.addEventListener('change', function() {
            // If class section is selected and it belongs to a semester, we can auto-select the semester
            const selectedClassOption = classSelect.options[classSelect.selectedIndex];
            const classSemesterId = selectedClassOption ? selectedClassOption.getAttribute('data-semester-id') : null;
            if (classSemesterId && semesterSelect.value !== classSemesterId) {
                semesterSelect.value = classSemesterId;
            }
            updateFilters();
        });

        // Run once on load to apply initial filtered states based on selected request parameters
        updateFilters();
    });
    </script>
@endsection
