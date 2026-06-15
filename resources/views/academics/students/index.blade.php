@extends('layouts.app')

@section('title', 'Students | SCSA Attendance')
@section('page-title', 'Students')
@section('page-subtitle', 'Enroll students manually or bulk upload a full class file')

@section('page-actions')
    <div class="actions" data-motion="fade-up">
        @if(!auth()->user()->isFeesDept())
            <a class="button secondary" href="{{ route('academics.index') }}">Academic hub</a>
            <a class="button secondary" href="{{ route('academics.students.import.create', array_filter(['class_section_id' => $filterSectionId])) }}">Bulk upload</a>
            <a class="button" href="{{ route('academics.students.create') }}">Add student</a>
        @endif
    </div>
@endsection

@section('content')
    <form class="card" method="get" action="{{ route('academics.students.index') }}" style="margin-bottom: 1.5rem; padding: 1.5rem;" data-motion="fade-up">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--color-scsa-line); padding-bottom: 0.75rem;">
            <h2 style="margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem; font-family: var(--font-display); font-weight: 700;">
                <span>🔍 Filter Students</span>
            </h2>
            @if($filterDepartmentId || $filterProgramId || $filterSemesterId || $filterDiv || $filterYear || $filterSectionId)
                <a href="{{ route('academics.students.index') }}" class="badge danger" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.725rem; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 999px;">
                    ✕ Clear Filters
                </a>
            @endif
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1.25rem; align-items: end;">
            <!-- Year -->
            <div class="field" style="margin-bottom: 0;">
                <label for="year" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-scsa-muted); font-weight: 700;">Year</label>
                <select id="year" name="year" onchange="this.form.submit()">
                    <option value="">All Years</option>
                    @foreach($yearsOfStudy as $yKey => $yName)
                        <option value="{{ $yKey }}" @selected($filterYear === $yKey)>{{ $yName }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Department -->
            <div class="field" style="margin-bottom: 0;">
                <label for="department_id" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-scsa-muted); font-weight: 700;">Department</label>
                <select id="department_id" name="department_id" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected($filterDepartmentId === $dept->id)>{{ $dept->department_code }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Program -->
            <div class="field" style="margin-bottom: 0;">
                <label for="program_id" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-scsa-muted); font-weight: 700;">Program</label>
                <select id="program_id" name="program_id" onchange="this.form.submit()">
                    <option value="">All Programs</option>
                    @foreach($programs as $prog)
                        <option value="{{ $prog->id }}" data-department-id="{{ $prog->department_id }}" @selected($filterProgramId === $prog->id)>{{ $prog->program_code }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Semester -->
            <div class="field" style="margin-bottom: 0;">
                <label for="semester_id" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-scsa-muted); font-weight: 700;">Sem</label>
                <select id="semester_id" name="semester_id" onchange="this.form.submit()">
                    <option value="">All Semesters</option>
                    @foreach($semesters as $sem)
                        <option value="{{ $sem->id }}" data-program-id="{{ $sem->program_id }}" @selected($filterSemesterId === $sem->id)>{{ $sem->semester_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Div / Section -->
            <div class="field" style="margin-bottom: 0;">
                <label for="div" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-scsa-muted); font-weight: 700;">Div</label>
                <select id="div" name="div" onchange="this.form.submit()">
                    <option value="">All Divs</option>
                    @foreach($divisions as $divisionName)
                        <option value="{{ $divisionName }}" @selected($filterDiv === $divisionName)>Division {{ $divisionName }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div class="card" data-motion="fade-up">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Enrollment</th>
                <th>Roll</th>
                <th>Class</th>
                <th>Login</th>
                <th>Status</th>
                @if(!auth()->user()->isFeesDept())
                    <th></th>
                @endif
            </tr>
            </thead>
            <tbody>
            @forelse($students as $student)
                <tr>
                    <td>{{ $student->user->name }}</td>
                    <td>{{ $student->enrollment_no }}</td>
                    <td>{{ $student->roll_no ?? '-' }}</td>
                    <td>{{ $student->classSection->display_name }}</td>
                    <td class="muted">{{ $student->user->username }}</td>
                    <td><span class="badge {{ $student->status === 'active' ? 'success' : 'warning' }}">{{ $student->status }}</span></td>
                    @if(!auth()->user()->isFeesDept())
                        <td>
                            <div class="actions" data-motion="fade-up">
                                <a class="button secondary" href="{{ route('academics.students.edit', $student) }}">Edit</a>
                                <form method="post" action="{{ route('academics.students.destroy', $student) }}" onsubmit="return confirm('Remove this student?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button danger" type="submit">Remove</button>
                                </form>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ auth()->user()->isFeesDept() ? 6 : 7 }}" class="muted">No students found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deptSelect = document.getElementById('department_id');
            const progSelect = document.getElementById('program_id');
            const semSelect = document.getElementById('semester_id');

            function filterPrograms() {
                const selectedDeptId = deptSelect.value;
                const progOptions = progSelect.querySelectorAll('option');

                progOptions.forEach(opt => {
                    if (opt.value === '') return;
                    const deptId = opt.getAttribute('data-department-id');
                    if (!selectedDeptId || deptId === selectedDeptId) {
                        opt.style.display = '';
                    } else {
                        opt.style.display = 'none';
                        if (opt.selected) {
                            progSelect.value = '';
                        }
                    }
                });
                filterSemesters();
            }

            function filterSemesters() {
                const selectedProgId = progSelect.value;
                const semOptions = semSelect.querySelectorAll('option');

                semOptions.forEach(opt => {
                    if (opt.value === '') return;
                    const progId = opt.getAttribute('data-program-id');
                    if (!selectedProgId || progId === selectedProgId) {
                        opt.style.display = '';
                    } else {
                        opt.style.display = 'none';
                        if (opt.selected) {
                            semSelect.value = '';
                        }
                    }
                });
            }

            deptSelect.addEventListener('change', filterPrograms);
            progSelect.addEventListener('change', filterSemesters);

            // Run on initial load
            filterPrograms();
        });
    </script>
@endsection
