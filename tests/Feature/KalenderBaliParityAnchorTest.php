<?php

namespace Tests\Feature;

use App\Services\Calendar\BaliCalendarService;
use Database\Seeders\VerifiedQaCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KalenderBaliParityAnchorTest extends TestCase
{
    use RefreshDatabase;

    public function test_august_30_2026_matches_kalender_bali_reference(): void
    {
        $this->seed(VerifiedQaCalendarMasterSeeder::class);

        $result = app(BaliCalendarService::class)->calculate('2026-08-30');

        $this->assertSame('Uye', $result->wuku->nama);
        $this->assertSame('Redite', $result->wewaran['sapta_wara']->nama);
        $this->assertSame('Indra', $result->wewaran['asta_wara']->nama);
        // kalenderbali.org: Luang Pepet Pasah Laba Wage Paniron Redite Indra Dangu Raksasa.
        $this->assertSame('Raksasa', $result->wewaran['dasa_wara']->nama);
        $this->assertSame('Dangu', $result->wewaran['sanga_wara']->nama);
    }

    public function test_pawukon_rerainan_match_official_2026_dates(): void
    {
        $this->seed(VerifiedQaCalendarMasterSeeder::class);

        // Surat Ditjen Bimas Hindu B-253/DJ.VI/Dt.VI.I.3/BA.03/09/2025 (config wariga.hindu_observances).
        foreach ([
            '2026-04-04' => ['Saniscara', 'Umanis', 'Watugunung'],
            '2026-04-08' => ['Buda', 'Kliwon', 'Sinta'],
            '2026-06-16' => ['Anggara', 'Wage', 'Dungulan'],
            '2026-06-17' => ['Buda', 'Kliwon', 'Dungulan'],
            '2026-06-18' => ['Wraspati', 'Umanis', 'Dungulan'],
            '2026-06-26' => ['Sukra', 'Wage', 'Kuningan'],
            '2026-06-27' => ['Saniscara', 'Kliwon', 'Kuningan'],
            '2026-10-31' => ['Saniscara', 'Umanis', 'Watugunung'],
            '2026-11-04' => ['Buda', 'Kliwon', 'Sinta'],
        ] as $date => [$sapta, $panca, $wuku]) {
            $result = app(BaliCalendarService::class)->calculate($date);

            $this->assertSame(
                [$sapta, $panca, $wuku],
                [$result->wewaran['sapta_wara']->nama, $result->wewaran['panca_wara']->nama, $result->wuku->nama],
                $date,
            );
        }
    }
}
