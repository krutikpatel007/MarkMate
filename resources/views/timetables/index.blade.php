@extends('layouts.app')

@section('title', 'Weekly Timetable | SCSA Attendance')
@section('page-title', 'Weekly Timetable')
@section('page-subtitle', 'Class-wise weekly grid for the selected section')

@section('page-actions')
    <div class="actions" style="flex-wrap: wrap;">
        @if($sections->isNotEmpty())
            <form method="get" action="{{ route('timetables.index') }}" class="actions" style="margin: 0;">
                <label class="muted" for="class_section_id" style="align-self: center;">Section</label>
                <select id="class_section_id" name="class_section_id" onchange="this.form.submit()" style="width: auto; min-width: 14rem;">
                    @foreach($sections as $s)
                        <option value="{{ $s->id }}" @selected($section && (int) $section->id === (int) $s->id)>{{ $s->display_name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
        <a class="button secondary" href="{{ route('timetables.faculty') }}">Faculty-wise View</a>
        <a class="button secondary" href="{{ route('timetables.slots') }}">All Slots</a>
        @if(auth()->user()->isAdmin() || auth()->user()->isHod())
            <a class="button secondary" href="{{ route('assignments.index') }}">Faculty Assignments</a>
            <a class="button" href="{{ route('timetables.create') }}">Add Slot</a>
        @endif
    </div>
@endsection

@section('content')
    @if(!$section)
        <div class="card">
            <p class="muted">No class sections are defined yet. Add programs and sections first.</p>
            <a class="button secondary" href="{{ route('setup.index') }}">Academic Setup</a>
        </div>
    @else
        <div class="card tt-card">
            <div class="tt-toolbar muted" style="margin-bottom: 0.75rem; font-size: 0.8125rem;">
                Regular lectures use one period; <strong>lab</strong> slots span two consecutive periods and appear as one merged cell. Saturday and Sunday slots appear under All Slots.
            </div>
            <div class="tt-scroll">
                <table class="tt-table" role="grid" aria-label="Weekly timetable for {{ $section->display_name }}">
                    <thead>
                    <tr class="tt-banner">
                        <th colspan="{{ 1 + count($day_columns) }}" scope="colgroup">{{ $university_name }}</th>
                    </tr>
                    <tr class="tt-banner tt-banner-sub">
                        <th colspan="{{ 1 + count($day_columns) }}" scope="colgroup">{{ $section->display_name }}</th>
                    </tr>
                    <tr>
                        <th class="tt-corner" scope="col"></th>
                        @foreach($day_columns as $d)
                            <th class="tt-day" scope="col">{{ $day_short_labels[$d] ?? 'DAY '.$d }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $ri => $row)
                        @if(($row['type'] ?? 'slot') === 'break')
                            <tr class="tt-break-row">
                                <th class="tt-time" scope="row">{{ $row['time_label'] ?? $row['label'] }}</th>
                                <td class="tt-break-cell" colspan="{{ count($day_columns) }}">{{ strtoupper($row['label']) }}</td>
                            </tr>
                        @else
                            <tr>
                                <th class="tt-time" scope="row">{{ $row['label'] }}</th>
                                @foreach($day_columns as $d)
                                    @if($covered[$ri][$d] ?? false)
                                        @continue
                                    @endif
                                    @php($cell = $cells[$ri][$d] ?? null)
                                    <td class="tt-cell {{ $cell && ($cell['slot_type'] ?? '') === 'lab' ? 'tt-cell-lab' : '' }}"
                                        @if($cell && ($cell['rowspan'] ?? 1) > 1) rowspan="{{ $cell['rowspan'] }}" @endif>
                                        @if($cell)
                                            <div class="tt-entry">{{ $cell['label'] }}</div>
                                        @else
                                            <span class="tt-empty">&nbsp;</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
