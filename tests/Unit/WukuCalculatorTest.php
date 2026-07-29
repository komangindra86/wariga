<?php

namespace Tests\Unit;

use App\Services\Calendar\WukuCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WukuCalculatorTest extends TestCase
{
    #[DataProvider('wukuBoundaries')]
    public function test_wuku_is_selected_for_each_seven_day_block(
        int $cycleIndex,
        int $expectedOrder
    ): void {
        $this->assertSame(
            $expectedOrder,
            (new WukuCalculator)->calculateOrder($cycleIndex)
        );
    }

    public static function wukuBoundaries(): array
    {
        return [
            'first day Sinta' => [0, 1],
            'last day Sinta' => [6, 1],
            'first day Landep' => [7, 2],
            'first day Watugunung' => [203, 30],
            'last cycle day' => [209, 30],
        ];
    }

    public function test_invalid_cycle_index_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new WukuCalculator)->calculateOrder(-1);
    }
}
