<?php

namespace App\Contracts;

use App\DTO\LunarDateResult;
use Carbon\Carbon;

interface LunarCalendarProviderInterface
{
    public function getForDate(Carbon $date): LunarDateResult;
}
