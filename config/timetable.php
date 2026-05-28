<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Printed timetable heading (admin / HOD weekly grid)
    |--------------------------------------------------------------------------
    */
    'university_name' => env('TIMETABLE_UNIVERSITY_NAME', env('APP_NAME', 'University')),

    /*
    |--------------------------------------------------------------------------
    | Week columns (ISO: Monday = 1 … Sunday = 7). Grid shows Mon–Fri only.
    |--------------------------------------------------------------------------
    */
    'grid_days' => [1, 2, 3, 4, 5],

    'day_short_labels' => [
        1 => 'MONDAY',
        2 => 'TUESDAY',
        3 => 'WEDNESDAY',
        4 => 'THURSDAY',
        5 => 'FRIDAY',
        6 => 'SATURDAY',
        7 => 'SUNDAY',
    ],

    /*
    |--------------------------------------------------------------------------
    | Standard period rows (time in 24h, labels as on printed timetable)
    |--------------------------------------------------------------------------
    */
    'grid_rows' => [
        ['type' => 'slot', 'start' => '09:00', 'end' => '09:55', 'label' => '9:00 TO 09:55'],
        ['type' => 'slot', 'start' => '10:00', 'end' => '10:55', 'label' => '10:00 TO 10:55'],
        ['type' => 'break', 'start' => '10:55', 'end' => '11:05', 'time_label' => '10:55 TO 11:05', 'label' => 'BREAK'],
        ['type' => 'slot', 'start' => '11:05', 'end' => '12:00', 'label' => '11:05 TO 12:00'],
        ['type' => 'slot', 'start' => '12:05', 'end' => '13:00', 'label' => '12:05 TO 1:00'],
        ['type' => 'break', 'start' => '13:00', 'end' => '13:40', 'time_label' => '1:00 TO 1:40', 'label' => 'LUNCH BREAK'],
        ['type' => 'slot', 'start' => '13:40', 'end' => '14:35', 'label' => '1:40 TO 2:35'],
        ['type' => 'slot', 'start' => '14:40', 'end' => '15:35', 'label' => '2:40 TO 3:35'],
    ],

];
