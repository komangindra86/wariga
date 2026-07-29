<?php

namespace Tests\Unit;

use App\Services\Calendar\BaliCalendarService;
use App\Support\PositiveModulo;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class CalendarEngineTest extends TestCase
{
    private BaliCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'wariga.engine_version' => '0.1.0-test',
            'wariga.reference_date' => '2024-02-29',
            'wariga.reference_cycle_index' => 0,
            'wariga.timezone' => 'Asia/Makassar',
        ]);

        $this->service = app(BaliCalendarService::class);
    }

    public function test_reference_date_returns_configured_cycle_index(): void
    {
        $result = $this->service->calculate('2024-02-29');

        $this->assertSame([
            'tanggal_masehi' => '2024-02-29',
            'cycle_index' => 0,
            'hari_ke_siklus_210' => 1,
            'engine_version' => '0.1.0-test',
        ], $result->toArray());
    }

    public function test_dates_before_and_after_reference_wrap_in_cycle(): void
    {
        $this->assertSame(209, $this->service->calculate('2024-02-28')->cycleIndex);
        $this->assertSame(1, $this->service->calculate('2024-03-01')->cycleIndex);
        $this->assertSame(7, $this->service->calculate('2024-03-07')->cycleIndex);
        $this->assertSame(208, $this->service->calculate('2024-02-27')->cycleIndex);
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
        config()->set('wariga.reference_date', '2024-02-28');

        $this->assertSame(1, $this->service->calculate('2024-02-29')->cycleIndex);
        $this->assertSame(2, $this->service->calculate('2024-03-01')->cycleIndex);
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

    public function test_calculation_is_rejected_when_reference_date_is_missing(): void
    {
        config()->set('wariga.reference_date');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Konfigurasi Calendar Engine belum lengkap');

        $this->service->calculate('2026-07-29');
    }

    public function test_calculation_uses_asia_makassar_timezone(): void
    {
        $utcDate = Carbon::parse('2024-02-29 16:30:00', 'UTC');

        $result = $this->service->calculate($utcDate);

        $this->assertSame('2024-03-01', $result->tanggalMasehi);
        $this->assertSame(1, $result->cycleIndex);
    }
}
