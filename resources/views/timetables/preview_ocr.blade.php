@extends('layouts.app')

@section('title', 'Verify Extracted Timetable | SCSA Attendance')
@section('page-title', 'Verify Extracted Timetable')
@section('page-subtitle', 'Review the AI-extracted timetable slots for ' . $section->display_name . '. Verify the matches, make corrections where needed, and save.')

@section('content')
    <form method="post" action="{{ route('timetables.save-ocr') }}">
        @csrf
        <input type="hidden" name="class_section_id" value="{{ $section->id }}">

        <div class="card tt-card" style="margin-bottom: 2rem;">
            <div class="tt-toolbar muted" style="margin-bottom: 1rem; font-size: 0.8125rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <div>
                    Uncheck any slot you do not want to save. Unmapped slots (marked in yellow) must be manually matched to a subject assignment to be saved.
                </div>
                <div>
                    <button class="button secondary small" type="button" onclick="toggleAllCheckboxes(this)">Uncheck All</button>
                </div>
            </div>

            @if($errors->any())
                <div class="alert error" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.8125rem;">
                    @foreach($errors->all() as $error)
                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <svg style="width: 1rem; height: 1rem; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                <span>{{ $error }}</span>
                            </svg>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="tt-scroll" style="overflow-x: auto;">
                <table class="tt-table" style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="background: #f1f5f9; border-bottom: 1px solid #cbd5e1; text-align: left;">
                            <th style="padding: 0.75rem; width: 3rem;">Save</th>
                            <th style="padding: 0.75rem; width: 6rem;">Day</th>
                            <th style="padding: 0.75rem; width: 8rem;">Time Block</th>
                            <th style="padding: 0.75rem; width: 12rem;">Extracted from Image</th>
                            <th style="padding: 0.75rem; width: 6rem;">Status</th>
                            <th style="padding: 0.75rem; min-width: 16rem;">Subject Assignment / Mapping</th>
                            <th style="padding: 0.75rem; width: 5rem;">Lecture No</th>
                            <th style="padding: 0.75rem; width: 6rem;">Slot Type</th>
                            <th style="padding: 0.75rem; width: 6rem;">Room/Label</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($slots as $index => $slot)
                            <tr style="border-bottom: 1px solid #e2e8f0; vertical-align: middle; background: {{ $slot['subject_assignment_id'] ? '#ffffff' : '#fffbeb' }};">
                                <td style="padding: 0.75rem; text-align: center;">
                                    <input type="checkbox" name="slots[{{ $index }}][enabled]" value="1" @checked($slot['subject_assignment_id'] !== null) class="slot-checkbox" onchange="toggleRowBg(this)">
                                    
                                    <input type="hidden" name="slots[{{ $index }}][day_of_week]" value="{{ $slot['day_of_week'] }}">
                                    <input type="hidden" name="slots[{{ $index }}][start_time]" value="{{ $slot['start_time'] }}">
                                    <input type="hidden" name="slots[{{ $index }}][end_time]" value="{{ $slot['end_time'] }}">
                                    <input type="hidden" name="slots[{{ $index }}][lecture_no]" value="{{ $slot['lecture_no'] }}">
                                    <input type="hidden" name="slots[{{ $index }}][slot_type]" value="{{ $slot['slot_type'] }}">
                                    <input type="hidden" name="slots[{{ $index }}][cell_label]" value="{{ $slot['cell_label'] }}">
                                </td>
                                <td style="padding: 0.75rem; font-weight: 600;">
                                    {{ $dayNames[$slot['day_of_week']] ?? 'Day ' . $slot['day_of_week'] }}
                                </td>
                                <td style="padding: 0.75rem;">
                                    <code style="font-weight: 700; font-size: 0.8125rem;">{{ substr($slot['start_time'], 0, 5) }} - {{ substr($slot['end_time'], 0, 5) }}</code>
                                </td>
                                <td style="padding: 0.75rem; line-height: 1.3;">
                                    <div style="font-weight: 600; color: #334155;">{{ $slot['subject_code'] ?: '(unknown subject)' }}</div>
                                    <div class="muted" style="font-size: 0.75rem;">Faculty Initials: {{ $slot['faculty_initials'] ?: '(none)' }}</div>
                                </td>
                                <td style="padding: 0.75rem;">
                                    @if($slot['subject_assignment_id'])
                                        <span class="badge" style="background: #d1fae5; color: #065f46; font-size: 0.75rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.25rem; display: inline-block;">Mapped</span>
                                    @else
                                        <span class="badge" style="background: #fef3c7; color: #92400e; font-size: 0.75rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.25rem; display: inline-block;">Unmapped</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem;">
                                    <select name="slots[{{ $index }}][subject_assignment_id]" style="width: 100%; margin: 0; padding: 0.35rem 0.5rem;" onchange="updateRowState(this, {{ $index }})">
                                        <option value="">-- Choose Subject Assignment --</option>
                                        @foreach($assignments as $assignment)
                                            <option value="{{ $assignment->id }}" @selected($slot['subject_assignment_id'] == $assignment->id)>
                                                {{ $assignment->subject->subject_code }} - {{ $assignment->faculty->user->name }} ({{ $assignment->faculty->display_initials }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="padding: 0.75rem; text-align: center;">
                                    {{ $slot['lecture_no'] }}
                                </td>
                                <td style="padding: 0.75rem; text-align: center; text-transform: uppercase; font-weight: 600; font-size: 0.75rem;">
                                    {{ $slot['slot_type'] }}
                                </td>
                                <td style="padding: 0.75rem;">
                                    {{ $slot['cell_label'] ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="padding: 2rem; text-align: center;" class="muted">
                                    No slots could be extracted from the timetable. Please ensure your image contains a clear grid.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="actions">
            <button class="button" type="submit" @disabled(empty($slots))>Save Selected Slots</button>
            <a class="button secondary" href="{{ route('timetables.upload-ocr') }}">Back to Upload</a>
        </div>
    </form>

    <script>
        function toggleRowBg(checkbox) {
            const row = checkbox.closest('tr');
            if (checkbox.checked) {
                const select = row.querySelector('select');
                if (select.value) {
                    row.style.background = '#ffffff';
                } else {
                    row.style.background = '#fffbeb';
                }
            } else {
                row.style.background = '#f8fafc';
            }
        }

        function updateRowState(select, index) {
            const row = select.closest('tr');
            const checkbox = row.querySelector('.slot-checkbox');
            const badge = row.querySelector('.badge');

            if (select.value) {
                checkbox.checked = true;
                row.style.background = '#ffffff';
                badge.textContent = 'Mapped';
                badge.style.background = '#d1fae5';
                badge.style.color = '#065f46';
            } else {
                row.style.background = '#fffbeb';
                badge.textContent = 'Unmapped';
                badge.style.background = '#fef3c7';
                badge.style.color = '#92400e';
            }
        }

        function toggleAllCheckboxes(btn) {
            const checkboxes = document.querySelectorAll('.slot-checkbox');
            const isUncheckAll = btn.textContent === 'Uncheck All';

            checkboxes.forEach(cb => {
                cb.checked = !isUncheckAll;
                toggleRowBg(cb);
            });

            btn.textContent = isUncheckAll ? 'Check All' : 'Uncheck All';
        }
    </script>
@endsection
