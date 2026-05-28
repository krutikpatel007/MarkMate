<?php

namespace App\Support;

use App\Models\Timetable;
use Illuminate\Support\Collection;

class TimetableGridPresenter
{
    /**
     * @param  Collection<int, Timetable>  $timetables
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     day_columns: list<int>,
     *     cells: array<int, array<int, array{label: string, rowspan: int, slot_type: string}|null>>,
     *     covered: array<int, array<int, bool>>
     * }
     */
    public static function build(Collection $timetables, ?array $gridRows = null, ?array $days = null): array
    {
        $gridRows ??= config('timetable.grid_rows', []);
        $days ??= config('timetable.grid_days', [1, 2, 3, 4, 5]);

        $cells = [];
        $covered = [];

        foreach ($gridRows as $ri => $_row) {
            foreach ($days as $d) {
                $cells[$ri][$d] = null;
                $covered[$ri][$d] = false;
            }
        }

        foreach ($timetables as $tt) {
            $day = (int) $tt->day_of_week;
            if (! in_array($day, $days, true)) {
                continue;
            }

            $rowIndices = TimetablePeriods::rowIndicesForSlot($tt->start_time, $tt->end_time);
            if ($rowIndices === []) {
                continue;
            }

            $firstRow = $rowIndices[0];
            $rowspan = count($rowIndices);
            $label = self::cellLabel($tt);

            if ($cells[$firstRow][$day] !== null) {
                $existing = $cells[$firstRow][$day]['label'];
                $cells[$firstRow][$day]['label'] = $existing."\n".$label;
                $cells[$firstRow][$day]['rowspan'] = max($cells[$firstRow][$day]['rowspan'], $rowspan);
            } else {
                $cells[$firstRow][$day] = [
                    'label' => $label,
                    'rowspan' => $rowspan,
                    'slot_type' => $tt->slot_type ?? TimetablePeriods::inferSlotType($tt->start_time, $tt->end_time),
                ];
            }

            for ($i = 1; $i < $rowspan; $i++) {
                $covered[$rowIndices[$i]][$day] = true;
            }
        }

        return [
            'rows' => $gridRows,
            'day_columns' => $days,
            'cells' => $cells,
            'covered' => $covered,
        ];
    }

    public static function cellLabel(Timetable $tt): string
    {
        $tt->loadMissing('subjectAssignment.subject', 'subjectAssignment.faculty.user');

        if ($tt->cell_label !== null && $tt->cell_label !== '') {
            return mb_strtoupper($tt->cell_label);
        }

        $code = $tt->subjectAssignment?->subject?->subject_code ?? '—';
        $faculty = $tt->subjectAssignment?->faculty;
        $ini = $faculty?->display_initials;
        if ($ini !== null && $ini !== '') {
            $label = $code.'/'.mb_strtoupper($ini);
        } else {
            $name = $faculty?->user?->name ?? '';
            $label = $code.'/'.self::initials($name);
        }

        if (($tt->slot_type ?? '') === 'lab' && ! str_contains(strtoupper($label), 'LAB')) {
            return $label.'/LAB';
        }

        return $label;
    }

    public static function initials(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '?';
        }

        $skip = ['dr', 'dr.', 'prof', 'prof.', 'mr', 'mr.', 'mrs', 'mrs.', 'ms', 'ms.'];
        $parts = preg_split('/\s+/u', $name) ?: [];
        $parts = array_values(array_filter($parts, function ($p) use ($skip) {
            $p = trim($p, '.');

            return $p !== '' && ! in_array(strtolower($p), $skip, true);
        }));

        $letters = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            $letters .= mb_strtoupper(mb_substr($p, 0, 1));
        }

        if ($letters === '' && $parts !== []) {
            $letters = mb_strtoupper(mb_substr($parts[0], 0, 2));
        }

        return $letters !== '' ? $letters : '?';
    }
}
