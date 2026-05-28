<div class="field">
    <label for="faculty_id">Faculty</label>
    <select id="faculty_id" name="faculty_id" required>
        <option value="">Select faculty</option>
        @foreach($faculty as $member)
            <option value="{{ $member->id }}" @selected(old('faculty_id', $assignment?->faculty_id) == $member->id)>
                {{ $member->user->name }}{{ $member->employee_code ? ' | '.$member->employee_code : '' }}
            </option>
        @endforeach
    </select>
</div>

<div class="field">
    <label for="subject_id">Subject</label>
    <select id="subject_id" name="subject_id" required>
        <option value="">Select subject</option>
        @foreach($subjects as $subject)
            <option value="{{ $subject->id }}" @selected(old('subject_id', $assignment?->subject_id) == $subject->id)>
                {{ $subject->subject_name }} | {{ $subject->program->program_code }} Sem {{ $subject->semester->semester_no }}
            </option>
        @endforeach
    </select>
</div>

<div class="field">
    <label for="class_section_id">Class / section</label>
    <select id="class_section_id" name="class_section_id" required>
        <option value="">Select class</option>
        @foreach($classSections as $section)
            <option value="{{ $section->id }}" @selected(old('class_section_id', $assignment?->class_section_id) == $section->id)>
                {{ $section->display_name }}
            </option>
        @endforeach
    </select>
</div>

<div class="grid grid-2">
    <div class="field">
        <label for="academic_year">Academic year</label>
        <input id="academic_year" name="academic_year" value="{{ old('academic_year', $assignment?->academic_year ?? '2026-27') }}" required>
    </div>

    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status" required>
            <option value="active" @selected(old('status', $assignment?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $assignment?->status ?? 'active') === 'inactive')>Inactive</option>
        </select>
    </div>
</div>
