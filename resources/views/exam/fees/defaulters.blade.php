@extends('layouts.app')

@section('title', 'Fee Defaulters | SCSA Attendance')
@section('page-title', 'Fee Defaulters Directory')
@section('page-subtitle', 'Track students with outstanding exam fee demands')

@section('content')
    <!-- Defaulters Stats & Actions -->
    <div class="grid grid-3" style="gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card" style="border-left: 5px solid var(--color-scsa-danger); padding: 1.25rem 1.5rem;">
            <span class="muted" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Total Defaulters</span>
            <div class="stat" style="color: var(--color-scsa-danger); font-size: 1.8rem; font-weight: 800; margin-top: 0.25rem;">
                {{ count($defaulters) }}
            </div>
            <span class="muted" style="font-size: 0.8rem; display: block; margin-top: 0.25rem;">Hall tickets are locked</span>
        </div>

        <div class="card" style="border-left: 5px solid var(--color-scsa-gold); padding: 1.25rem 1.5rem;">
            <span class="muted" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Total Outstanding</span>
            <div class="stat" style="color: var(--color-scsa-gold); font-size: 1.8rem; font-weight: 800; margin-top: 0.25rem;">
                ₹{{ number_format(count($defaulters) * 1000, 2) }}
            </div>
            <span class="muted" style="font-size: 0.8rem; display: block; margin-top: 0.25rem;">₹1,000.00 per student demand</span>
        </div>

        <div class="card" style="padding: 1.25rem 1.5rem; display: flex; align-items: center; justify-content: center; gap: 0.75rem;">
            <a href="{{ route('exam-fees.admin.defaulters.export', request()->query()) }}" class="button" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; width: 100%; justify-content: center;">
                📥 Export Defaulters CSV
            </a>
        </div>
    </div>

    <!-- Interactive Filter Panel -->
    <section class="card" style="padding: 1.5rem; margin-bottom: 2rem; border-radius: var(--border-radius-xl);">
        <h2 style="font-size: 1.05rem; margin-top: 0; margin-bottom: 1rem; border-bottom: 0; padding-bottom: 0;">Filter Defaulters</h2>
        <form method="get" action="{{ route('exam-fees.admin.defaulters') }}" id="filterForm">
            <div class="grid grid-4" style="gap: 1rem;">
                <div class="field" style="margin-bottom: 0;">
                    <label class="muted" style="font-size: 0.75rem; font-weight: 700;">Search Student</label>
                    <input type="text" name="search" placeholder="Name or Enrollment No..." value="{{ $filterSearch }}" style="width: 100%; padding: 0.45rem;">
                </div>

                <div class="field" style="margin-bottom: 0;">
                    <label class="muted" style="font-size: 0.75rem; font-weight: 700;">Department</label>
                    <select name="department_id" id="department_id" onchange="filterPrograms()" style="width: 100%;">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ $filterDepartmentId === $dept->id ? 'selected' : '' }}>
                                {{ $dept->department_name }} ({{ $dept->department_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field" style="margin-bottom: 0;">
                    <label class="muted" style="font-size: 0.75rem; font-weight: 700;">Program</label>
                    <select name="program_id" id="program_id" onchange="filterSemesters()" style="width: 100%;">
                        <option value="">All Programs</option>
                        @foreach($programs as $prog)
                            <option value="{{ $prog->id }}" data-dept="{{ $prog->department_id }}" {{ $filterProgramId === $prog->id ? 'selected' : '' }}>
                                {{ $prog->program_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field" style="margin-bottom: 0;">
                    <label class="muted" style="font-size: 0.75rem; font-weight: 700;">Semester</label>
                    <select name="semester_id" id="semester_id" style="width: 100%;">
                        <option value="">All Semesters</option>
                        @foreach($semesters as $sem)
                            <option value="{{ $sem->id }}" data-prog="{{ $sem->program_id }}" {{ $filterSemesterId === $sem->id ? 'selected' : '' }}>
                                {{ $sem->program->program_code }} - Sem {{ $sem->semester_no }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-top: 1.25rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
                @if($filterDepartmentId || $filterProgramId || $filterSemesterId || $filterSearch)
                    <a href="{{ route('exam-fees.admin.defaulters') }}" class="button secondary" style="text-decoration: none; padding: 0.5rem 1rem;">
                        Clear Filters
                    </a>
                @endif
                <button type="submit" class="button">Apply Filters</button>
            </div>
        </form>
    </section>

    <!-- Defaulters Table -->
    <section class="card" style="padding: 1.5rem; border-radius: var(--border-radius-xl); box-shadow: var(--shadow-sm);">
        <h2 style="margin-bottom: 1rem;">Defaulters List</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Enrollment No.</th>
                        <th>Student Name</th>
                        <th>Department</th>
                        <th>Program</th>
                        <th>Semester</th>
                        <th>Outstanding</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($defaulters as $student)
                        <tr>
                            <td><strong>{{ $student->enrollment_no }}</strong></td>
                            <td>{{ $student->user->name }}</td>
                            <td>{{ $student->semester->program->department->department_code }}</td>
                            <td>{{ $student->semester->program->program_code }}</td>
                            <td>Sem {{ $student->semester->semester_no }}</td>
                            <td style="color: var(--color-scsa-danger); font-weight: 600;">₹1,000.00</td>
                            <td>
                                <span class="badge danger" style="font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.4rem;">
                                    Defaulter
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ route('exam-fees.admin.index', ['enrollment_no' => $student->enrollment_no]) }}#manual-payment" class="button secondary" style="font-size: 0.75rem; padding: 0.35rem 0.75rem; min-height: unset;">
                                    Clear Fee
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="muted" style="text-align: center; padding: 3rem 0;">
                                No outstanding fee defaulters found matching the criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script>
        // Interactive client side filtering for program & semester selectors
        const allPrograms = Array.from(document.getElementById('program_id').options);
        const allSemesters = Array.from(document.getElementById('semester_id').options);

        function filterPrograms() {
            const deptId = document.getElementById('department_id').value;
            const programSelect = document.getElementById('program_id');
            
            // Reset program option
            programSelect.value = "";
            
            // Clear and filter program options
            programSelect.innerHTML = "";
            allPrograms.forEach(opt => {
                if (!opt.value || !deptId || opt.getAttribute('data-dept') === deptId) {
                    programSelect.appendChild(opt);
                }
            });

            filterSemesters();
        }

        function filterSemesters() {
            const progId = document.getElementById('program_id').value;
            const semesterSelect = document.getElementById('semester_id');
            const deptId = document.getElementById('department_id').value;

            // Reset semester option
            semesterSelect.value = "";

            semesterSelect.innerHTML = "";
            allSemesters.forEach(opt => {
                const optProgId = opt.getAttribute('data-prog');
                const programOpt = allPrograms.find(p => p.value === optProgId);
                const optDeptId = programOpt ? programOpt.getAttribute('data-dept') : null;

                const matchProg = !progId || optProgId === progId;
                const matchDept = !deptId || optDeptId === deptId;

                if (!opt.value || (matchProg && matchDept)) {
                    semesterSelect.appendChild(opt);
                }
            });
        }

        // Initialize lists on load
        document.addEventListener("DOMContentLoaded", () => {
            const deptId = document.getElementById('department_id').value;
            const progId = document.getElementById('program_id').value;
            
            if (deptId) {
                // Filter programs to match selected department
                const programSelect = document.getElementById('program_id');
                programSelect.innerHTML = "";
                allPrograms.forEach(opt => {
                    if (!opt.value || opt.getAttribute('data-dept') === deptId) {
                        programSelect.appendChild(opt);
                    }
                });
                programSelect.value = progId;
            }

            if (deptId || progId) {
                const semesterSelect = document.getElementById('semester_id');
                const activeSemVal = semesterSelect.value;
                semesterSelect.innerHTML = "";
                allSemesters.forEach(opt => {
                    const optProgId = opt.getAttribute('data-prog');
                    const programOpt = allPrograms.find(p => p.value === optProgId);
                    const optDeptId = programOpt ? programOpt.getAttribute('data-dept') : null;

                    const matchProg = !progId || optProgId === progId;
                    const matchDept = !deptId || optDeptId === deptId;

                    if (!opt.value || (matchProg && matchDept)) {
                        semesterSelect.appendChild(opt);
                    }
                });
                semesterSelect.value = activeSemVal;
            }
        });
    </script>
@endsection
