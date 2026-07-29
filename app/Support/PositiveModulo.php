<?php

namespace App\Support;

use InvalidArgumentException;

final class PositiveModulo
{
    public static function positiveModulo(int $number, int $modulus): int
    {
        if ($modulus <= 0) {
            throw new InvalidArgumentException('Modulus harus lebih besar dari nol.');
        }

        return (($number % $modulus) + $modulus) % $modulus;
    }
}
