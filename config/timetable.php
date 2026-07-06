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
        ['type' => 'slot', 'start' => '09:15', 'end' => '10:15', 'label' => '9:15 TO 10:15'],
        ['type' => 'slot', 'start' => '10:15', 'end' => '11:15', 'label' => '10:15 TO 11:15'],
        ['type' => 'slot', 'start' => '11:15', 'end' => '12:15', 'label' => '11:15 TO 12:15'],
        ['type' => 'slot', 'start' => '12:15', 'end' => '13:15', 'label' => '12:15 TO 1:15'],
        ['type' => 'break', 'start' => '13:15', 'end' => '14:00', 'time_label' => '1:15 TO 2:00', 'label' => 'LUNCH BREAK'],
        ['type' => 'slot', 'start' => '14:00', 'end' => '15:00', 'label' => '2:00 TO 3:00'],
        ['type' => 'slot', 'start' => '15:00', 'end' => '16:00', 'label' => '3:00 TO 4:00'],
    ],

];
