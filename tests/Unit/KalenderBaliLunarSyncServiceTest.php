<?php

namespace Tests\Unit;

use App\Services\Lunar\KalenderBaliLunarSyncService;
use Database\Seeders\BaliCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KalenderBaliLunarSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BaliCalendarMasterSeeder::class);
    }

    public function test_parses_regular_lunar_day(): void
    {
        $row = app(KalenderBaliLunarSyncService::class)->parseDailyPage('2026-08-30', <<<'HTML'
        <a title= "Purnama, Tilem, Penanggal, dan Pangelong"> Pangelong 3 </a>
        <a title= "Sasih"> Ketiga </a>
        HTML);

        $this->assertSame('pangelong', $row['fase']);
        $this->assertSame(3, $row['nomor_hari']);
        $this->assertFalse($row['is_tilem']);
    }

    public function test_parses_purnama_and_nampih_sasih(): void
    {
        $row = app(KalenderBaliLunarSyncService::class)->parseDailyPage('2026-07-29', <<<'HTML'
        <a title="Purnama, Tilem, Penanggal, dan Pangelong">Purnama</a>
        <a title="Sasih">Nampih Karo</a>
        HTML);

        $this->assertSame('penanggal', $row['fase']);
        $this->assertSame(15, $row['nomor_hari']);
        $this->assertTrue($row['is_purnama']);
        $this->assertTrue($row['is_nampih_sasih']);
    }

    public function test_normalizes_source_sasih_spelling(): void
    {
        $row = app(KalenderBaliLunarSyncService::class)->parseDailyPage('2026-01-01', <<<'HTML'
        <a title="Purnama, Tilem, Penanggal, dan Pangelong">Penanggal 13</a>
        <a title="Sasih">Kepitu</a>
        HTML);

        $this->assertSame(13, $row['nomor_hari']);
        $this->assertSame('penanggal', $row['fase']);
    }
}
