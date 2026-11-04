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

    public function test_nampih_sasih_matches_kalender_bali(): void
    {
        $calculator = new PengalantakaLunarCalculator;

        // kalenderbali.org/purnamatilem.php?tahun=2027
        $jiyestha = $calculator->calculate('2027-05-05');
        $this->assertTrue($jiyestha->isTilem);
        $this->assertSame('jiyestha', $jiyestha->sasih['kode']);
        $this->assertFalse($jiyestha->isNampihSasih);

        $mala = $calculator->calculate('2027-05-20');
        $this->assertTrue($mala->isPurnama);
        $this->assertSame('jiyestha', $mala->sasih['kode']);
        $this->assertTrue($mala->isNampihSasih);

        $sadha = $calculator->calculate('2027-06-19');
        $this->assertTrue($sadha->isPurnama);
        $this->assertSame('sadha', $sadha->sasih['kode']);

        // Sebelum Mala Sadha 2024: Tilem Kasanga 10 Maret 2024 (Nyepi 11 Maret 2024).
        $kasanga = $calculator->calculate('2024-03-10');
        $this->assertTrue($kasanga->isTilem);
        $this->assertSame('kasanga', $kasanga->sasih['kode']);
    }

    public function test_pengunalatri_that_swallows_tilem_is_labelled_tilem(): void
    {
        $calculator = new PengalantakaLunarCalculator;

        // Tilem Kenam 28 Desember 2027 jatuh pada hari pengunalatri.
        $this->assertTrue($calculator->isPengunalatri('2027-12-28'));
        $tilem = $calculator->calculate('2027-12-28');
        $this->assertTrue($tilem->isTilem);
        $this->assertSame('kanem', $tilem->sasih['kode']);
    }

    public function test_nampih_follows_nineteen_year_metonic_cycle(): void
    {
        $calculator = new PengalantakaLunarCalculator;

        // Tilem sasih Mala menurut kalenderbali.org/purnamatilem.php, di luar tabel lama 2024-2037.
        foreach ([
            '2005-07-06' => 'sadha', '2008-06-03' => 'jiyestha', '2013-07-07' => 'sadha',
            '2019-07-02' => 'sadha', '2022-05-30' => 'jiyestha', '2038-07-02' => 'sadha',
            '2041-05-30' => 'jiyestha', '2043-07-07' => 'sadha',
        ] as $date => $sasih) {
            $result = $calculator->calculate($date);
            $this->assertTrue($result->isTilem, $date);
            $this->assertTrue($result->isNampihSasih, $date);
            $this->assertSame($sasih, $result->sasih['kode'], $date);
        }

        // Tahun tanpa nampih tetap berurutan: Tilem Kasa 2040 bukan Mala.
        $this->assertFalse($calculator->calculate('2040-07-09')->isNampihSasih);
    }

    public function test_calculates_dates_outside_verified_dataset(): void
    {
        $result = (new PengalantakaLunarCalculator)->calculate('2027-01-01');

        $this->assertTrue($result->available);
        $this->assertSame('calculated', $result->verificationStatus);
        $this->assertNotNull($result->sasih);
    }
}
