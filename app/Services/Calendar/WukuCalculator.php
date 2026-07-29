<?php

namespace App\Services\Calendar;

use InvalidArgumentException;

final class WukuCalculator
{
    public function calculateOrder(int $cycleIndex): int
    {
        if ($cycleIndex < 0 || $cycleIndex >= 210) {
            throw new InvalidArgumentException('Indeks siklus harus berada di antara 0 dan 209.');
        }

        return intdiv($cycleIndex, 7) + 1;
    }
}
