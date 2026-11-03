<?php

return [
    'reference_disclosure' => 'Perhitungan dilakukan oleh mesin Wariga Bali Santih. '
        .'Sebagian hasil dibandingkan dengan Kalender Bali Digital (kalenderbali.org) '
        .'sebagai referensi eksternal. Aplikasi ini tidak berafiliasi dengan KalenderBali.org.',
    'api_rate_limit' => (int) env('WARIGA_API_RATE_LIMIT', 60),
    'timezone' => env('WARIGA_TIMEZONE', 'Asia/Makassar'),
    'engine_version' => env('WARIGA_ENGINE_VERSION', '0.3.0'),
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

    // SKB 3 Menteri No. 1497/2025, 2/2025, dan 5/2025.
    'holidays' => [
        '2026-01-01' => ['Tahun Baru 2026 Masehi'],
        '2026-01-16' => ['Isra Mikraj Nabi Muhammad S.A.W.'],
        '2026-02-17' => ['Tahun Baru Imlek 2577 Kongzili'],
        '2026-03-19' => ['Hari Suci Nyepi Tahun Baru Saka 1948'],
        '2026-03-21' => ['Idul Fitri 1447 Hijriah'],
        '2026-03-22' => ['Idul Fitri 1447 Hijriah'],
        '2026-04-03' => ['Wafat Yesus Kristus'],
        '2026-04-05' => ['Kebangkitan Yesus Kristus (Paskah)'],
        '2026-05-01' => ['Hari Buruh Internasional'],
        '2026-05-14' => ['Kenaikan Yesus Kristus'],
        '2026-05-27' => ['Idul Adha 1447 Hijriah'],
        '2026-05-31' => ['Hari Raya Waisak 2570 BE'],
        '2026-06-01' => ['Hari Lahir Pancasila'],
        '2026-06-16' => ['1 Muharam Tahun Baru Islam 1448 Hijriah'],
        '2026-08-17' => ['Proklamasi Kemerdekaan'],
        '2026-08-25' => ['Maulid Nabi Muhammad S.A.W.'],
        '2026-12-25' => ['Kelahiran Yesus Kristus'],
    ],

    // Surat Ditjen Bimas Hindu Kemenag RI B-253/DJ.VI/Dt.VI.I.3/BA.03/09/2025.
    'hindu_observances' => [
        '2026-01-17' => ['Hari Suci Siwa Ratri'],
        '2026-03-18' => ['Hari Tawur Agung Kesanga'],
        '2026-03-19' => ['Hari Suci Nyepi Tahun Baru Saka 1948'],
        '2026-03-20' => ['Ngembak Geni'],
        '2026-04-04' => ['Hari Raya Saraswati'],
        '2026-04-08' => ['Hari Raya Pagerwesi'],
        '2026-06-16' => ['Hari Penampahan Galungan'],
        '2026-06-17' => ['Hari Raya Galungan'],
        '2026-06-18' => ['Hari Umanis Galungan'],
        '2026-06-26' => ['Hari Penampahan Kuningan'],
        '2026-06-27' => ['Hari Raya Kuningan'],
        '2026-10-31' => ['Hari Raya Saraswati'],
        '2026-11-04' => ['Hari Raya Pagerwesi'],
        '2026-11-07' => ['Hari Deepavali/Diwali'],
    ],
];
