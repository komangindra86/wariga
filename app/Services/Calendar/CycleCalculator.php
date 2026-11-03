<?php

namespace App\Services\Calendar;

use App\Support\PositiveModulo;
use Carbon\Carbon;

final class CycleCalculator
{
    private const CYCLE_LENGTH = 210;

    /**
     * Epoch pawukon dikunci di kode, tidak bisa diubah lewat env maupun panel admin.
     * 29-07-2026 = Buda Paing Krulut (indeks 115; indeks 0 = Redite Paing Sinta).
     * Diverifikasi terhadap KalenderBali.org: 17-06-2026 = Galungan (Buda Kliwon Dungulan, 73)
     * dan 30-08-2026 = Redite Wage Uye (147).
     */
    public const EPOCH_DATE = '2026-07-29';

    public const EPOCH_CYCLE_INDEX = 115;

    public function calculateCycleIndex(Carbon $date): int
    {
        $timezone = (string) config('wariga.timezone', 'Asia/Makassar');
        $epoch = Carbon::createFromFormat('!Y-m-d', self::EPOCH_DATE, $timezone);
        $targetDate = $date->copy()->setTimezone($timezone)->startOfDay();

        return PositiveModulo::positiveModulo(
            self::EPOCH_CYCLE_INDEX + (int) $epoch->diffInDays($targetDate, false),
            self::CYCLE_LENGTH
        );
    }

    /**
     * Tanggal Redite Paing Sinta (indeks 0) terakhir pada atau sebelum epoch.
     */
    public static function cycleStartDate(): string
    {
        return Carbon::createFromFormat('!Y-m-d', self::EPOCH_DATE)
            ->subDays(self::EPOCH_CYCLE_INDEX)
            ->format('Y-m-d');
    }
}
