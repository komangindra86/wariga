<?php

namespace Tests\Unit;

use App\Services\Lunar\PengalantakaLunarCalculator;
use PHPUnit\Framework\TestCase;

class PengalantakaLunarCalculatorTest extends TestCase
{
    public function test_anchor_and_phase_boundaries(): void
    {
        $calculator = new PengalantakaLunarCalculator;

        $this->assertSame('penanggal', $calculator->calculate('2026-02-02')->fase);
        $this->assertSame(15, $calculator->calculate('2026-02-02')->nomorHari);
        $this->assertTrue($calculator->calculate('2026-02-02')->isPurnama);
        $this->assertSame('pangelong', $calculator->calculate('2026-02-03')->fase);
        $this->assertSame(2, $calculator->calculate('2026-02-03')->nomorHari);
    }

    public function test_pengunalatri_jumps_every_sixty_three_days(): void
    {
        $calculator = new PengalantakaLunarCalculator;
        foreach ([
            '2026-02-03' => 2,
            '2026-04-07' => 6,
            '2026-06-09' => 10,
            '2026-08-11' => 14,
        ] as $date => $day) {
            $this->assertTrue($calculator->isPengunalatri($date));
            $this->assertSame($day, $calculator->calculate($date)->nomorHari);
        }
    }

    public function test_calculates_dates_outside_verified_dataset(): void
    {
        $result = (new PengalantakaLunarCalculator)->calculate('2027-01-01');

        $this->assertTrue($result->available);
        $this->assertSame('calculated', $result->verificationStatus);
        $this->assertNotNull($result->sasih);
    }
}
