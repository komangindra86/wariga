<?php

namespace App\Services\Calendar;

use App\Support\PositiveModulo;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class CycleCalculator
{
    private const CYCLE_LENGTH = 210;

    public function calculateCycleIndex(Carbon $date): int
    {
        $timezone = (string) config('wariga.timezone', 'Asia/Makassar');
        $referenceDate = $this->referenceDate($timezone);
        $targetDate = $date->copy()->setTimezone($timezone)->startOfDay();
        $dayDifference = (int) $referenceDate->diffInDays($targetDate, false);
        $referenceCycleIndex = (int) config('wariga.reference_cycle_index', 0);

        return PositiveModulo::positiveModulo(
            $referenceCycleIndex + $dayDifference,
            self::CYCLE_LENGTH
        );
    }

    private function referenceDate(string $timezone): Carbon
    {
        $configuredDate = config('wariga.reference_date');

        if (! is_string($configuredDate) || trim($configuredDate) === '') {
            throw new RuntimeException(
                'Konfigurasi Calendar Engine belum lengkap: WARIGA_REFERENCE_DATE belum tersedia.'
            );
        }

        try {
            return Carbon::createFromFormat(
                '!Y-m-d',
                $configuredDate,
                $timezone
            )->startOfDay();
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                'Konfigurasi WARIGA_REFERENCE_DATE harus menggunakan format YYYY-MM-DD.',
                previous: $exception
            );
        }
    }
}
