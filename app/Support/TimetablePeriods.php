<?php

namespace App\Support;

use Carbon\Carbon;

class TimetablePeriods
{
    /**
     * @return list<array{start: string, end: string, label: string}>
     */
    public static function teachingPeriods(): array
    {
        $periods = [];
        foreach (config('timetable.grid_rows', []) as $row) {
            if (($row['type'] ?? 'slot') === 'break') {
                continue;
            }
            $periods[] = [
                'start' => $row['start'],
                'end' => $row['end'],
                'label' => $row['label'],
            ];
        }

        return $periods;
    }

    /**
     * Two consecutive teaching periods combined (for lab blocks).
     *
     * @return list<array{start: string, end: string, label: string, period_start: int}>
     */
    public static function labPairs(): array
    {
        $periods = self::teachingPeriods();
        $pairs = [];

        for ($i = 0; $i < count($periods) - 1; $i++) {
            $pairs[] = [
                'start' => $periods[$i]['start'],
                'end' => $periods[$i + 1]['end'],
                'label' => 'Lab: '.$periods[$i]['label'].' + '.$periods[$i + 1]['label'],
                'period_start' => $i + 1,
            ];
        }

        return $pairs;
    }

    /**
     * @return list<int> Row indices in grid_rows that overlap the given times.
     */
    public static function rowIndicesForSlot(string $startTime, string $endTime): array
    {
        $indices = [];
        foreach (config('timetable.grid_rows', []) as $ri => $row) {
            if (($row['type'] ?? 'slot') === 'break') {
                continue;
            }
            if (self::timesOverlap($startTime, $endTime, $row['start'], $row['end'])) {
                $indices[] = $ri;
            }
        }

        return $indices;
    }

    public static function timesOverlap(string $dbStart, string $dbEnd, string $rowStart, string $rowEnd): bool
    {
        $ds = self::clock(substr($dbStart, 0, 8));
        $de = self::clock(substr($dbEnd, 0, 8));
        $rs = self::clock($rowStart);
        $re = self::clock($rowEnd);

        return $ds->lt($re) && $de->gt($rs);
    }

    public static function matchesSinglePeriod(string $startTime, string $endTime): bool
    {
        foreach (self::teachingPeriods() as $period) {
            if (self::normalize($startTime) === self::normalize($period['start'])
                && self::normalize($endTime) === self::normalize($period['end'])) {
                return true;
            }
        }

        return false;
    }

    public static function matchesLabPair(string $startTime, string $endTime): bool
    {
        foreach (self::labPairs() as $pair) {
            if (self::normalize($startTime) === self::normalize($pair['start'])
                && self::normalize($endTime) === self::normalize($pair['end'])) {
                return true;
            }
        }

        return false;
    }

    public static function inferSlotType(string $startTime, string $endTime): string
    {
        if (self::matchesLabPair($startTime, $endTime)) {
            return 'lab';
        }

        return 'regular';
    }

    private static function normalize(string $time): string
    {
        return substr(strlen($time) <= 5 ? $time.':00' : $time, 0, 8);
    }

    private static function clock(string $hms): Carbon
    {
        $t = strlen($hms) <= 5 ? $hms.':00' : $hms;

        return Carbon::parse('2000-01-01 '.$t);
    }
}
