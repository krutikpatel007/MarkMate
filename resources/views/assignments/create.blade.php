@extends('layouts.app')

@section('title', 'New Faculty Assignment | SCSA Attendance')
@section('page-title', 'Assign faculty to subjects')
@section('page-subtitle', 'One faculty member can teach multiple subjects in the same class — select all that apply')

@section('content')
    <div class="card" style="max-width: 52rem;">
        <form method="post" action="{{ route('assignments.store') }}" id="assignment-form">
            @csrf

            <div class="field">
                <label for="faculty_id">Faculty</label>
                <select id="faculty_id" name="faculty_id" required>
                    <option value="">Select faculty</option>
                    @foreach($faculty as $member)
                        <option value="{{ $member->id }}" @selected(old('faculty_id') == $member->id)>
                            {{ $member->user->name }}{{ $member->employee_code ? ' | '.$member->employee_code : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="class_section_id">Class / section</label>
                <select id="class_section_id" name="class_section_id" required>
                    <option value="">Select class</option>
                    @foreach($classSections as $section)
                        <option value="{{ $section->id }}"
                                data-program="{{ $section->program_id }}"
                                data-semester="{{ $section->semester_id }}"
                                @selected(old('class_section_id') == $section->id)>
                            {{ $section->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="academic_year">Academic year</label>
                <input id="academic_year" name="academic_year" value="{{ old('academic_year', '2026-27') }}" required>
            </div>

            <div class="field">
                <label>Subjects <span class="muted">(select one or more)</span></label>
                <p class="muted" id="subject-hint" style="margin: 0 0 0.5rem; font-size: 0.8125rem;">
                    Choose a class first to list subjects for that semester.
                </p>
                <div id="subject-list" class="radio-row" style="flex-direction: column; align-items: stretch; gap: 0.35rem;">
                    @foreach($subjects as $subject)
                        <label class="radio-option subject-option"
                               style="display: none;"
                               data-program="{{ $subject->program_id }}"
                               data-semester="{{ $subject->semester_id }}"
                               data-key-prefix="">
                            <input type="checkbox"
                                   name="subject_ids[]"
                                   value="{{ $subject->id }}"
                                   @checked(is_array(old('subject_ids')) && in_array($subject->id, old('subject_ids')))>
                            <span>
                                <strong>{{ $subject->subject_code }}</strong> — {{ $subject->subject_name }}
                                <span class="muted">({{ $subject->program->program_code }} Sem {{ $subject->semester->semester_no }})</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('subject_ids')
                    <div class="muted" style="color: var(--color-scsa-danger); margin-top: 0.35rem;">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                </select>
            </div>

            <div class="actions">
                <button class="button" type="submit">Save assignments</button>
                <a class="button secondary" href="{{ route('assignments.index') }}">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    (function () {
        const existingKeys = @json($existingAssignmentKeys);
        const facultySelect = document.getElementById('faculty_id');
        const classSelect = document.getElementById('class_section_id');
        const yearInput = document.getElementById('academic_year');
        const hint = document.getElementById('subject-hint');
        const options = document.querySelectorAll('.subject-option');

        function assignmentKey(facultyId, subjectId, classId, year) {
            return `${facultyId}-${subjectId}-${classId}-${year}`;
        }

        function refreshSubjects() {
            const classOpt = classSelect.options[classSelect.selectedIndex];
            const programId = classOpt?.dataset.program;
            const semesterId = classOpt?.dataset.semester;
            const facultyId = facultySelect.value;
            const classId = classSelect.value;
            const year = yearInput.value.trim();
            let visible = 0;

            options.forEach((label) => {
                const input = label.querySelector('input');
                const matchClass = programId && semesterId
                    && label.dataset.program === programId
                    && label.dataset.semester === semesterId;

                if (!matchClass) {
                    label.style.display = 'none';
                    input.checked = false;
                    input.disabled = true;
                    return;
                }

                const key = assignmentKey(facultyId, input.value, classId, year);
                const taken = facultyId && classId && year && existingKeys.includes(key);

                label.style.display = 'flex';
                input.disabled = taken;
                label.style.opacity = taken ? '0.55' : '1';
                if (taken) {
                    input.checked = false;
                    label.title = 'Already assigned';
                } else {
                    label.title = '';
                    visible++;
                }
            });

            hint.textContent = visible
                ? 'Check every subject this faculty teaches in the selected class.'
                : 'No subjects found for this class semester, or all are already assigned.';
        }

        facultySelect.addEventListener('change', refreshSubjects);
        classSelect.addEventListener('change', refreshSubjects);
        yearInput.addEventListener('input', refreshSubjects);
        refreshSubjects();
    })();
    </script>
@endsection
