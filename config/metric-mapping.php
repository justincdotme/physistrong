<?php

return [
    'resistance' => [
        'required' => ['load', 'reps'],
        'optional' => ['intensity'],
    ],
    'timed_hold' => [
        'required' => ['duration'],
        'optional' => ['load', 'intensity'],
    ],
    'distance' => [
        'required' => ['distance'],
        'optional' => ['duration', 'cardio_settings', 'intensity'],
    ],
    'interval' => [
        'required' => ['interval_header'],
        'optional' => ['cardio_settings', 'distance', 'intensity'],
    ],
];
