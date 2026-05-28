@php
    $selectedProgramId = old('program_id', $programId ?? null);
    $selectedSemesterId = old('semester_id', $semesterId ?? null);
@endphp

<div class="grid grid-2">
    <div class="field" data-motion="fade-up">
        <label for="program_id">Program</label>
        <select id="program_id" name="program_id" required>
            <option value="">Select program</option>
            @foreach($programs as $program)
                <option value="{{ $program->id }}" @selected((int) $selectedProgramId === (int) $program->id)>
                    {{ $program->program_code }} — {{ $program->program_name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="field" data-motion="fade-up">
        <label for="semester_id">Semester</label>
        <select id="semester_id" name="semester_id" required>
            <option value="">Select semester</option>
            @foreach($programs as $program)
                @foreach($program->semesters as $semester)
                    <option value="{{ $semester->id }}"
                            data-program="{{ $program->id }}"
                            @selected((int) $selectedSemesterId === (int) $semester->id)>
                        {{ $program->program_code }} — {{ $semester->semester_name }}
                    </option>
                @endforeach
            @endforeach
        </select>
    </div>
</div>

<script>
(function () {
    const programSelect = document.getElementById('program_id');
    const semesterSelect = document.getElementById('semester_id');
    if (!programSelect || !semesterSelect) return;

    const allOptions = Array.from(semesterSelect.querySelectorAll('option[data-program]'));

    function filterSemesters() {
        const programId = programSelect.value;
        allOptions.forEach((opt) => {
            const show = !programId || opt.dataset.program === programId;
            opt.hidden = !show;
            opt.disabled = !show;
        });
        const selected = semesterSelect.selectedOptions[0];
        if (selected && selected.disabled) {
            semesterSelect.value = '';
        }
    }

    programSelect.addEventListener('change', filterSemesters);
    filterSemesters();
})();
</script>
