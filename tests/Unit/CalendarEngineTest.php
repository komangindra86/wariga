<?php

namespace Tests\Unit;

use App\Services\Calendar\BaliCalendarService;
use App\Services\Calendar\CycleCalculator;
use App\Support\PositiveModulo;
use Carbon\Carbon;
use Database\Seeders\BaliCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CalendarEngineTest extends TestCase
{
    use RefreshDatabase;

    private BaliCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'wariga.engine_version' => '0.1.0-test',
            'wariga.timezone' => 'Asia/Makassar',
        ]);

        $this->seed(BaliCalendarMasterSeeder::class);
        $this->service = app(BaliCalendarService::class);
    }

    public function test_cycle_start_is_redite_paing_sinta(): void
    {
        $this->assertSame('2026-04-05', CycleCalculator::cycleStartDate());

        $result = $this->service->calculate('2026-04-05');

        $this->assertSame('2026-04-05', $result->tanggalMasehi);
        $this->assertSame(0, $result->cycleIndex);
        $this->assertSame(1, $result->hariKeSiklus210);
        $this->assertSame('Sinta', $result->wuku->nama);
        $this->assertSame([
            'eka_wara' => null,
            'dwi_wara' => 'Menga',
            'tri_wara' => 'Pasah',
            'catur_wara' => 'Sri',
            'panca_wara' => 'Paing',
            'sad_wara' => 'Tungleh',
            'sapta_wara' => 'Redite',
            'asta_wara' => 'Sri',
            'sanga_wara' => 'Dangu',
            'dasa_wara' => 'Sri',
        ], array_map(
            fn ($element) => $element?->nama,
            $result->wewaran,
        ));
        $this->assertSame('0.1.0-test', $result->engineVersion);
    }

    public function test_epoch_matches_galungan_anchor(): void
    {
        $epoch = $this->service->calculate(CycleCalculator::EPOCH_DATE);
        $galungan = $this->service->calculate('2026-06-17');

        $this->assertSame(CycleCalculator::EPOCH_CYCLE_INDEX, $epoch->cycleIndex);
        $this->assertSame(73, $galungan->cycleIndex);
        $this->assertSame('Dungulan', $galungan->wuku->nama);
        $this->assertSame('Buda', $galungan->wewaran['sapta_wara']->nama);
        $this->assertSame('Kliwon', $galungan->wewaran['panca_wara']->nama);
    }

    public function test_epoch_cannot_be_overridden_from_configuration(): void
    {
        config()->set([
            'wariga.reference_date' => '2024-02-29',
            'wariga.reference_cycle_index' => 0,
        ]);

        $this->assertSame(73, $this->service->calculate('2026-06-17')->cycleIndex);
    }

    public function test_dates_before_and_after_cycle_start_wrap_in_cycle(): void
    {
        $this->assertSame(209, $this->service->calculate('2026-04-04')->cycleIndex);
        $this->assertSame(1, $this->service->calculate('2026-04-06')->cycleIndex);
        $this->assertSame(7, $this->service->calculate('2026-04-12')->cycleIndex);
        $this->assertSame(208, $this->service->calculate('2026-04-03')->cycleIndex);
    }

    public function test_cycle_returns_to_same_index_after_210_days(): void
    {
        $reference = Carbon::createFromFormat('!Y-m-d', '2024-02-29', 'Asia/Makassar');

        $this->assertSame(
            $this->service->calculate($reference)->cycleIndex,
            $this->service->calculate($reference->copy()->addDays(210))->cycleIndex
        );
    }

    public function test_leap_year_boundary_counts_each_calendar_day(): void
    {
        $this->assertSame(64, $this->service->calculate('2028-02-28')->cycleIndex);
        $this->assertSame(65, $this->service->calculate('2028-02-29')->cycleIndex);
        $this->assertSame(66, $this->service->calculate('2028-03-01')->cycleIndex);
    }

    public function test_positive_modulo_handles_negative_numbers(): void
    {
        $this->assertSame(209, PositiveModulo::positiveModulo(-1, 210));
        $this->assertSame(0, PositiveModulo::positiveModulo(-210, 210));
        $this->assertSame(1, PositiveModulo::positiveModulo(-209, 210));
    }

    public function test_positive_modulo_rejects_invalid_modulus(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PositiveModulo::positiveModulo(1, 0);
    }

    public function test_calculation_uses_asia_makassar_timezone(): void
    {
        $utcDate = Carbon::parse('2026-04-05 16:30:00', 'UTC');

        $result = $this->service->calculate($utcDate);

        $this->assertSame('2026-04-06', $result->tanggalMasehi);
        $this->assertSame(1, $result->cycleIndex);
    }
}
