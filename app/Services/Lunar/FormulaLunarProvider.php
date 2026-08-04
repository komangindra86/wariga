<?php

namespace App\Services\Lunar;

use App\Contracts\LunarCalendarProviderInterface;
use App\DTO\LunarDateResult;
use Carbon\Carbon;

final class FormulaLunarProvider implements LunarCalendarProviderInterface
{
    public function __construct(private readonly PengalantakaLunarCalculator $calculator) {}

    public function getForDate(Carbon $date): LunarDateResult
    {
        return $this->calculator->calculate($date);
    }
}
