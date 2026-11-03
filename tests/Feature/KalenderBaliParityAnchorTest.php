<?php

namespace Tests\Feature;

use App\Contracts\LunarCalendarProviderInterface;
use App\Services\Calendar\BaliCalendarService;
use App\Services\Calendar\CalendarObservanceService;
use Carbon\Carbon;
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

    public function test_sasih_rerainan_are_calculated_without_configured_dates(): void
    {
        $this->seed(VerifiedQaCalendarMasterSeeder::class);
        config(['wariga.holidays' => [], 'wariga.hindu_observances' => []]);

        // 2026: Surat Bimas Hindu B-253/2025. 2027-01-06: Siwa Ratri kalenderbali.org. 2023-2025: Nyepi resmi.
        foreach ([
            '2026-01-17' => 'Hari Suci Siwa Ratri',
            '2026-03-18' => 'Hari Tawur Agung Kesanga',
            '2026-03-19' => 'Hari Suci Nyepi Tahun Baru Saka 1948',
            '2026-03-20' => 'Ngembak Geni',
            '2027-01-06' => 'Hari Suci Siwa Ratri',
            '2023-03-22' => 'Hari Suci Nyepi Tahun Baru Saka 1945',
            '2024-03-11' => 'Hari Suci Nyepi Tahun Baru Saka 1946',
            '2025-03-29' => 'Hari Suci Nyepi Tahun Baru Saka 1947',
        ] as $date => $name) {
            $this->assertContains($name, $this->observanceNames($date), $date);
        }

        $this->assertNotContains('Hari Suci Siwa Ratri', $this->observanceNames('2027-01-05'));
        $this->assertNotContains('Hari Suci Siwa Ratri', $this->observanceNames('2027-01-07'));
    }

    /**
     * @return list<string>
     */
    private function observanceNames(string $date): array
    {
        $calendar = app(BaliCalendarService::class)->calculate($date)->toArray();
        $lunar = app(LunarCalendarProviderInterface::class)->getForDate(Carbon::parse($date))->toArray();

        return array_column(app(CalendarObservanceService::class)->forDate($date, $calendar, $lunar)['observances'], 'name');
    }
}
