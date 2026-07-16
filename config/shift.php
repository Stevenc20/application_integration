<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shift Boundaries
    |--------------------------------------------------------------------------
    |
    | Each shift has a start and end time. These values are used by the Timeline
    | Engine to determine cutoff points. Jobs exceeding these boundaries are
    | flagged as overflow and sent to the recovery queue.
    |
    */

    'Shift Pagi' => [
        'start' => '07:30',
        'end'   => '21:00',
    ],

    'Shift Malam' => [
        'start' => '21:30',
        'end'   => '07:30',
    ],

];
