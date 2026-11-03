<?php

namespace Tests\Feature;

use App\Models\WarigaActivity;
use App\Models\AlaAyuMaster;
use App\Services\Wariga\DewasaSearchService;
use Carbon\Carbon;
use Database\Seeders\BaliCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class DewasaSearchPhaseSevenTest extends TestCase
{
    use RefreshDatabase;

    private WarigaActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BaliCalendarMasterSeeder::class);
        config([
            'wariga.search.cache_ttl' => 3600,
        ]);
        Cache::clear();

        $this->activity = WarigaActivity::query()->create([
            'kode' => 'SEARCH-TEST',
            'nama' => 'Pencarian Test',
            'slug' => 'pencarian-test',
            'kategori' => 'Test',
            'deskripsi' => 'Kegiatan untuk pengujian.',
            'disclaimer' => 'Hanya untuk pengujian.',
            'urutan' => 1,
            'is_active' => true,
        ]);
        $master = AlaAyuMaster::query()->create([
            'nama' => 'Hari Pengujian',
            'deskripsi' => 'Pengetahuan untuk menguji pencarian.',
            'komposisi_teks' => 'Semua Sapta Wara',
            'komposisi' => collect(['Redite', 'Soma', 'Anggara', 'Buda', 'Wraspati', 'Sukra', 'Saniscara'])
                ->map(fn ($name) => ['raw' => $name, 'tokens' => [$name]])->all(),
            'sumber_nama' => 'Sumber pengujian',
            'sumber_url' => 'https://example.test/reference',
            'diimpor_pada' => now(),
        ]);
        DB::table('ala_ayu_activity_rules')->insert([
            'activity_id' => $this->activity->id,
            'ala_ayu_master_id' => $master->id,
            'effect' => 'positive',
            'source_url' => 'https://example.test/reference',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_searches_one_seven_thirty_days_and_cross_month(): void
    {
        $service = app(DewasaSearchService::class);

        foreach ([
            ['2026-07-29', '2026-07-29', 1],
            ['2026-07-29', '2026-08-04', 7],
            ['2026-07-15', '2026-08-13', 30],
        ] as [$start, $end, $expected]) {
            $result = $service->search(
                $this->activity->id,
                Carbon::parse($start),
                Carbon::parse($end),
                ['limit' => 100],
            );

            $this->assertSame($expected, $result->totalDatesChecked);
            $this->assertCount($expected, $result->recommendations);
        }
    }

    public function test_ranking_prioritizes_status_then_score_and_nearest_date(): void
    {
        $result = app(DewasaSearchService::class)->search(
            $this->activity->id,
            Carbon::parse('2026-07-29'),
            Carbon::parse('2026-07-31'),
            ['limit' => 3, 'mode' => 'expert'],
        );

        $this->assertSame('2026-07-29', $result->recommendations[0]->date);
        $this->assertSame(60, $result->recommendations[0]->score);
        $this->assertSame('2026-07-30', $result->recommendations[1]->date);
        $this->assertSame(60, $result->recommendations[1]->score);
        $this->assertSame('2026-07-31', $result->recommendations[2]->date);
    }

    public function test_uses_calculated_lunar_fallback_and_supports_modes_and_filter(): void
    {
        $result = app(DewasaSearchService::class)->search(
            $this->activity->id,
            Carbon::parse('2026-07-29'),
            Carbon::parse('2026-07-29'),
            ['mode' => 'complete', 'status_filter' => 'recommended'],
        );

        $this->assertSame(1, $result->recommendedCount);
        $this->assertTrue($result->recommendations[0]->lunar['available']);
        $this->assertSame('calculated', $result->recommendations[0]->lunar['verification_status']);
        $this->assertArrayHasKey('calendar', $result->toArray()['recommendations'][0]);
    }

    public function test_rejects_invalid_and_more_than_366_day_ranges(): void
    {
        $service = app(DewasaSearchService::class);

        try {
            $service->search(
                $this->activity->id,
                Carbon::parse('2026-01-01'),
                Carbon::parse('2027-01-02'),
            );
            $this->fail('Rentang lebih dari 366 hari seharusnya ditolak.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Rentang pencarian publik maksimal 366 hari.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $service->search(
            $this->activity->id,
            Carbon::parse('2026-07-29'),
            Carbon::parse('2026-07-28'),
        );
    }

    public function test_cache_key_changes_when_verified_rule_changes(): void
    {
        $service = app(DewasaSearchService::class);
        $first = $service->search(
            $this->activity->id,
            Carbon::parse('2026-07-29'),
            Carbon::parse('2026-07-29'),
        );
        $this->assertSame(60, $first->recommendations[0]->score);
        DB::table('ala_ayu_activity_rules')->where('activity_id', $this->activity->id)
            ->update(['effect' => 'negative', 'updated_at' => now()->addSecond()]);

        $second = $service->search(
            $this->activity->id,
            Carbon::parse('2026-07-29'),
            Carbon::parse('2026-07-29'),
        );
        $this->assertSame(30, $second->recommendations[0]->score);
        DB::table('ala_ayu_activity_rules')->where('activity_id', $this->activity->id)
            ->update(['effect' => 'positive', 'updated_at' => now()->addSeconds(2)]);
        $third = $service->search(
            $this->activity->id,
            Carbon::parse('2026-07-29'),
            Carbon::parse('2026-07-29'),
        );
        $this->assertSame(60, $third->recommendations[0]->score);
    }

    public function test_expert_detail_exposes_explainable_rule_fields(): void
    {
        $this->getJson('/api/v1/dewasa/pencarian-test/2026-07-29')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'engine_version',
                    'rule_version',
                    'recommendations' => [[
                        'main_reasons' => [[
                            'rule_code',
                            'rule_name',
                            'matched',
                            'score_effect',
                            'severity',
                            'source_name',
                            'source_page',
                            'verification_status',
                            'rule_version',
                            'matched_composition',
                            'alahing_priority',
                        ]],
                    ]],
                ],
            ]);
    }

}
