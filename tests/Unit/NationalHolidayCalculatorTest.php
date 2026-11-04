<?php

namespace Tests\Unit;

use App\Services\Calendar\NationalHolidayCalculator;
use PHPUnit\Framework\TestCase;

class NationalHolidayCalculatorTest extends TestCase
{
    public function test_2026_matches_skb_three_ministers(): void
    {
        // SKB 3 Menteri No. 1497/2025, 2/2025, dan 5/2025 (tanpa Nyepi, dihitung dari sasih).
        $this->assertSame([
            '2026-01-01' => ['Tahun Baru 2026 Masehi'],
            '2026-01-16' => ['Isra Mikraj Nabi Muhammad S.A.W.'],
            '2026-02-17' => ['Tahun Baru Imlek 2577 Kongzili'],
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
        ], (new NationalHolidayCalculator)->forYear(2026));
    }

    public function test_islamic_holidays_follow_kemenag_hisab(): void
    {
        $calculator = new NationalHolidayCalculator;

        // Tanggal resmi pemerintah; 2023 Idul Fitri/Adha berbeda dari kalender wujudul hilal.
        foreach ([
            '2019-06-05' => 'Idul Fitri 1440 Hijriah',
            '2022-05-02' => 'Idul Fitri 1443 Hijriah',
            '2023-04-22' => 'Idul Fitri 1444 Hijriah',
            '2023-06-29' => 'Idul Adha 1444 Hijriah',
            '2024-04-10' => 'Idul Fitri 1445 Hijriah',
            '2024-06-17' => 'Idul Adha 1445 Hijriah',
            '2025-01-27' => 'Isra Mikraj Nabi Muhammad S.A.W.',
            '2025-03-31' => 'Idul Fitri 1446 Hijriah',
            '2025-06-06' => 'Idul Adha 1446 Hijriah',
            '2025-09-05' => 'Maulid Nabi Muhammad S.A.W.',
        ] as $date => $name) {
            $this->assertContains($name, $calculator->forDate($date), $date);
        }
    }

    public function test_lunar_based_holidays_match_published_dates(): void
    {
        $calculator = new NationalHolidayCalculator;

        foreach ([
            '2004-01-22' => 'Tahun Baru Imlek 2555 Kongzili',
            '2015-02-19' => 'Tahun Baru Imlek 2566 Kongzili',
            '2025-01-29' => 'Tahun Baru Imlek 2576 Kongzili',
            '2015-06-02' => 'Hari Raya Waisak 2559 BE',
            '2020-05-07' => 'Hari Raya Waisak 2564 BE',
            '2023-06-04' => 'Hari Raya Waisak 2567 BE',
            '2025-05-12' => 'Hari Raya Waisak 2569 BE',
            '2025-04-20' => 'Kebangkitan Yesus Kristus (Paskah)',
            '2027-03-28' => 'Kebangkitan Yesus Kristus (Paskah)',
        ] as $date => $name) {
            $this->assertContains($name, $calculator->forDate($date), $date);
        }
    }
}
