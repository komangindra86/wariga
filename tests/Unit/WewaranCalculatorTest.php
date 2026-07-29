<?php

namespace Tests\Unit;

use App\Services\Calendar\WewaranCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class WewaranCalculatorTest extends TestCase
{
    private WewaranCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new WewaranCalculator;
    }

    public function test_first_pawukon_day_has_expected_wewaran_orders(): void
    {
        $this->assertSame([
            'eka_wara' => null,
            'dwi_wara' => 1,
            'tri_wara' => 1,
            'catur_wara' => 1,
            'panca_wara' => 1,
            'sad_wara' => 1,
            'sapta_wara' => 1,
            'asta_wara' => 1,
            'sanga_wara' => 1,
            'dasa_wara' => 5,
        ], $this->calculator->calculate(0, 9, 5));
    }

    public function test_eka_dwi_and_dasa_wara_are_derived_from_urip(): void
    {
        $result = $this->calculator->calculate(1, 7, 4);

        $this->assertSame(1, $result['eka_wara']);
        $this->assertSame(2, $result['dwi_wara']);
        $this->assertSame(2, $result['dasa_wara']);
    }

    public function test_simple_wewaran_cycles_repeat_at_their_lengths(): void
    {
        $day = $this->calculator->calculate(0, 9, 5);

        $this->assertSame($day['tri_wara'], $this->calculator->calculate(3, 8, 7)['tri_wara']);
        $this->assertSame($day['panca_wara'], $this->calculator->calculate(5, 9, 6)['panca_wara']);
        $this->assertSame($day['sad_wara'], $this->calculator->calculate(6, 7, 9)['sad_wara']);
        $this->assertSame($day['sapta_wara'], $this->calculator->calculate(7, 4, 5)['sapta_wara']);
    }

    public function test_jaya_and_kala_are_repeated_on_first_three_days_of_dungulan(): void
    {
        foreach ([70, 71, 72] as $cycleIndex) {
            $result = $this->calculator->calculate($cycleIndex, 9, 5);

            $this->assertSame(3, $result['catur_wara']);
            $this->assertSame(7, $result['asta_wara']);
        }

        $afterPause = $this->calculator->calculate(73, 8, 7);
        $this->assertSame(4, $afterPause['catur_wara']);
        $this->assertSame(8, $afterPause['asta_wara']);
    }

    public function test_dangu_is_repeated_on_first_four_pawukon_days(): void
    {
        foreach ([0, 1, 2, 3] as $cycleIndex) {
            $this->assertSame(
                1,
                $this->calculator->calculate($cycleIndex, 9, 5)['sanga_wara']
            );
        }

        $this->assertSame(2, $this->calculator->calculate(4, 5, 8)['sanga_wara']);
    }

    public function test_invalid_cycle_index_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculate(210, 9, 5);
    }
}
