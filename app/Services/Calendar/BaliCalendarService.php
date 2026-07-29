<?php

namespace App\Services\Calendar;

use App\DTO\BaliDateResult;
use Carbon\Carbon;
use InvalidArgumentException;
use Throwable;

final class BaliCalendarService
{
    public function __construct(
        private readonly CycleCalculator $cycleCalculator
    ) {}

    public function calculate(Carbon|string $date): BaliDateResult
    {
        $timezone = (string) config('wariga.timezone', 'Asia/Makassar');
        $calendarDate = $this->normalizeDate($date, $timezone);
        $cycleIndex = $this->cycleCalculator->calculateCycleIndex($calendarDate);

        return new BaliDateResult(
            tanggalMasehi: $calendarDate->format('Y-m-d'),
            cycleIndex: $cycleIndex,
            hariKeSiklus210: $cycleIndex + 1,
            engineVersion: (string) config('wariga.engine_version', '0.1.0'),
        );
    }

    private function normalizeDate(Carbon|string $date, string $timezone): Carbon
    {
        if ($date instanceof Carbon) {
            return $date->copy()->setTimezone($timezone)->startOfDay();
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $date, $timezone)->startOfDay();
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                'Tanggal Masehi harus menggunakan format YYYY-MM-DD.',
                previous: $exception
            );
        }
    }
}
