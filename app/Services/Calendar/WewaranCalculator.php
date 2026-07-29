<?php

namespace App\Services\Calendar;

use App\Support\PositiveModulo;
use InvalidArgumentException;

final class WewaranCalculator
{
    /**
     * Menghasilkan urutan master untuk seluruh Wewaran.
     *
     * @return array<string, int|null>
     */
    public function calculate(int $cycleIndex, int $pancaUrip, int $saptaUrip): array
    {
        if ($cycleIndex < 0 || $cycleIndex >= 210) {
            throw new InvalidArgumentException('Indeks siklus harus berada di antara 0 dan 209.');
        }

        $uripSum = $pancaUrip + $saptaUrip;
        $isOdd = $uripSum % 2 !== 0;

        return [
            'eka_wara' => $isOdd ? 1 : null,
            'dwi_wara' => $isOdd ? 2 : 1,
            'tri_wara' => $this->cyclicOrder($cycleIndex, 3),
            'catur_wara' => $this->caturOrder($cycleIndex),
            'panca_wara' => $this->cyclicOrder($cycleIndex, 5),
            'sad_wara' => $this->cyclicOrder($cycleIndex, 6),
            'sapta_wara' => $this->cyclicOrder($cycleIndex, 7),
            'asta_wara' => $this->astaOrder($cycleIndex),
            'sanga_wara' => $cycleIndex <= 3
                ? 1
                : $this->cyclicOrder($cycleIndex - 3, 9),
            'dasa_wara' => PositiveModulo::positiveModulo($uripSum, 10) + 1,
        ];
    }

    private function cyclicOrder(int $cycleIndex, int $length): int
    {
        return PositiveModulo::positiveModulo($cycleIndex, $length) + 1;
    }

    private function caturOrder(int $cycleIndex): int
    {
        if ($cycleIndex >= 70 && $cycleIndex <= 72) {
            return 3;
        }

        return $this->cyclicOrder($cycleIndex > 72 ? $cycleIndex - 2 : $cycleIndex, 4);
    }

    private function astaOrder(int $cycleIndex): int
    {
        if ($cycleIndex >= 70 && $cycleIndex <= 72) {
            return 7;
        }

        return $this->cyclicOrder($cycleIndex > 72 ? $cycleIndex - 2 : $cycleIndex, 8);
    }
}
