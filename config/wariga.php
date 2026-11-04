<?php

return [
    'reference_disclosure' => 'Perhitungan dilakukan oleh mesin Wariga Bali Santih. '
        .'Sebagian hasil dibandingkan dengan Kalender Bali Digital (kalenderbali.org) '
        .'sebagai referensi eksternal. Aplikasi ini tidak berafiliasi dengan KalenderBali.org.',
    'api_rate_limit' => (int) env('WARIGA_API_RATE_LIMIT', 60),
    'timezone' => env('WARIGA_TIMEZONE', 'Asia/Makassar'),
    'engine_version' => env('WARIGA_ENGINE_VERSION', '0.4.0'),
    'rule_version' => env('WARIGA_RULE_VERSION', '1.0.0'),
    'dataset_version' => env('WARIGA_DATASET_VERSION', '1.0.0'),
    'initial_score' => (int) env('WARIGA_INITIAL_SCORE', 50),

    'search' => [
        'max_range_days' => 366,
        'chunk_size' => 31,
        'default_limit' => 10,
        'max_limit' => 100,
        'cache_ttl' => (int) env('WARIGA_SEARCH_CACHE_TTL', 3600),
    ],

    'classifications' => [
        ['min' => 85, 'max' => 100, 'label' => 'Sangat Direkomendasikan'],
        ['min' => 70, 'max' => 84, 'label' => 'Direkomendasikan'],
        ['min' => 55, 'max' => 69, 'label' => 'Cukup Baik'],
        ['min' => 40, 'max' => 54, 'label' => 'Perlu Pertimbangan'],
        ['min' => 0, 'max' => 39, 'label' => 'Sebaiknya Dihindari'],
    ],

    'critical_prohibition_label' => 'Sebaiknya Dihindari',
];
