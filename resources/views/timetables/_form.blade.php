@php
    $slotType = old('slot_type', $timetable?->slot_type ?? 'regular');
    $periods = \App\Support\TimetablePeriods::teachingPeriods();
    $labPairs = \App\Support\TimetablePeriods::labPairs();
@endphp

<div class="field">
    <label for="subject_assignment_id">Subject and class assignment</label>
    <select id="subject_assignment_id" name="subject_assignment_id" required>
        <option value="">Select assignment</option>
        @foreach($assignments as $assignment)
            <option value="{{ $assignment->id }}"
                @selected(old('subject_assignment_id', $timetable?->subject_assignment_id) == $assignment->id)>
                {{ $assignment->subject->subject_name }}
                | {{ $assignment->classSection->display_name }}
                | {{ $assignment->faculty->user->name }}
                ({{ $assignment->academic_year }})
            </option>
        @endforeach
    </select>
</div>

<div class="grid grid-2">
    <div class="field">
        <label for="day_of_week">Day</label>
        <select id="day_of_week" name="day_of_week" required>
            @foreach($dayNames as $num => $label)
                <option value="{{ $num }}" @selected((int) old('day_of_week', $timetable?->day_of_week ?? 1) === $num)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="lecture_no">Lecture number</label>
        <input id="lecture_no" name="lecture_no" type="number" min="1" max="20" required
               value="{{ old('lecture_no', $timetable?->lecture_no ?? '') }}">
    </div>
</div>

<div class="field">
    <label for="slot_type">Slot type</label>
    <select id="slot_type" name="slot_type" required>
        <option value="regular" @selected($slotType === 'regular')>Regular lecture (55 min, one period)</option>
        <option value="lab" @selected($slotType === 'lab')>Lab (two consecutive periods)</option>
    </select>
    <p class="muted" style="margin: 0.35rem 0 0; font-size: 0.8125rem;">
        Use <strong>Lab</strong> when the session spans two back-to-back periods (e.g. 11:05–1:00). The weekly grid will show one merged cell.
    </p>
</div>

<div class="field" id="period_regular_wrap">
    <label for="period_regular">Teaching period</label>
    <select id="period_regular">
        <option value="">Select period (auto-fills times)</option>
        @foreach($periods as $i => $period)
            <option value="{{ $period['start'] }}|{{ $period['end'] }}"
                data-start="{{ $period['start'] }}"
                data-end="{{ $period['end'] }}">
                {{ $period['label'] }}
            </option>
        @endforeach
    </select>
</div>

<div class="field" id="period_lab_wrap" style="display: none;">
    <label for="period_lab">Lab block (2 periods)</label>
    <select id="period_lab">
        <option value="">Select lab block (auto-fills times)</option>
        @foreach($labPairs as $pair)
            <option value="{{ $pair['start'] }}|{{ $pair['end'] }}"
                data-start="{{ $pair['start'] }}"
                data-end="{{ $pair['end'] }}">
                {{ $pair['label'] }}
            </option>
        @endforeach
    </select>
</div>

<div class="grid grid-2">
    <div class="field">
        <label for="start_time">Start time</label>
        <input id="start_time" name="start_time" type="time" required
               value="{{ old('start_time', $timetable?->start_time ? substr($timetable->start_time, 0, 5) : '') }}">
    </div>
    <div class="field">
        <label for="end_time">End time</label>
        <input id="end_time" name="end_time" type="time" required
               value="{{ old('end_time', $timetable?->end_time ? substr($timetable->end_time, 0, 5) : '') }}">
    </div>
</div>

<div class="field">
    <label for="cell_label">Grid label <span class="muted">(optional, e.g. WAD/DP/LAB-1)</span></label>
    <input id="cell_label" name="cell_label" maxlength="64"
           placeholder="Leave blank to auto-generate from subject + faculty"
           value="{{ old('cell_label', $timetable?->cell_label) }}">
</div>

<div class="field">
    <label for="status">Status</label>
    <select id="status" name="status" required>
        <option value="active" @selected(old('status', $timetable?->status ?? 'active') === 'active')>Active</option>
        <option value="inactive" @selected(old('status', $timetable?->status ?? 'active') === 'inactive')>Inactive</option>
    </select>
</div>

<script>
(function () {
    const slotType = document.getElementById('slot_type');
    const regularWrap = document.getElementById('period_regular_wrap');
    const labWrap = document.getElementById('period_lab_wrap');
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');
    const periodRegular = document.getElementById('period_regular');
    const periodLab = document.getElementById('period_lab');

    function applyPeriod(select) {
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.dataset.start) return;
        startInput.value = opt.dataset.start;
        endInput.value = opt.dataset.end;
    }

    function syncTypeUi() {
        const isLab = slotType.value === 'lab';
        regularWrap.style.display = isLab ? 'none' : '';
        labWrap.style.display = isLab ? '' : 'none';
    }

    slotType.addEventListener('change', syncTypeUi);
    periodRegular.addEventListener('change', () => applyPeriod(periodRegular));
    periodLab.addEventListener('change', () => applyPeriod(periodLab));

    syncTypeUi();

    const start = startInput.value;
    const end = endInput.value;
    if (start && end) {
        const match = (sel) => {
            for (const opt of sel.options) {
                if (opt.dataset.start === start && opt.dataset.end === end) {
                    sel.value = opt.value;
                    return true;
                }
            }
            return false;
        };
        if (slotType.value === 'lab') {
            match(periodLab);
        } else {
            match(periodRegular);
        }
    }
})();
</script>
