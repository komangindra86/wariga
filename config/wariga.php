<?php

return [
    'engine_version' => env('WARIGA_ENGINE_VERSION', '0.1.0'),
    'reference_date' => env('WARIGA_REFERENCE_DATE'),
    'reference_cycle_index' => (int) env(
        'WARIGA_REFERENCE_CYCLE_INDEX',
        0
    ),
    'timezone' => 'Asia/Makassar',
];
