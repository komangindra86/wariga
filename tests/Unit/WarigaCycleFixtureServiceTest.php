<?php

namespace Tests\Unit;

use App\Services\Testing\WarigaCycleFixtureService;
use Database\Seeders\BaliCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WarigaCycleFixtureServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BaliCalendarMasterSeeder::class);
    }

    public function test_fixture_covers_exactly_one_complete_cycle(): void
    {
        $rows = app(WarigaCycleFixtureService::class)->generate('2026-04-05');

        $this->assertCount(210, $rows);
        $this->assertSame(range(0, 209), array_column($rows, 'cycle_index'));
        $this->assertCount(30, array_unique(array_column($rows, 'wuku')));
        $this->assertSame('2026-04-05', $rows[0]['tanggal']);
        $this->assertSame('2026-10-31', $rows[209]['tanggal']);

        foreach ($rows as $row) {
            $this->assertSame(
                (int) $row['urip_sapta'] + (int) $row['urip_panca'],
                (int) $row['jumlah_urip'],
            );
            $this->assertSame('belum_divalidasi_ahli', $row['formula_status']);
        }
    }

    public function test_comparison_reports_matches_differences_and_missing_references(): void
    {
        $service = app(WarigaCycleFixtureService::class);
        $system = array_slice($service->generate('2026-04-05'), 0, 3);
        $expert = array_map(
            fn (array $row): array => array_map('strval', $row),
            array_slice($system, 0, 2),
        );
        $expert[1]['wuku'] = 'Referensi Berbeda';

        $result = $service->compare($system, $expert);

        $this->assertSame(1, $result['matched']);
        $this->assertSame(1, $result['different']);
        $this->assertSame(1, $result['missing']);
        $this->assertSame('cocok', $result['results'][0]['status']);
        $this->assertSame('berbeda', $result['results'][1]['status']);
        $this->assertStringContainsString('wuku', $result['results'][1]['catatan_validator']);
        $this->assertSame('referensi_tidak_tersedia', $result['results'][2]['status']);
    }
}
