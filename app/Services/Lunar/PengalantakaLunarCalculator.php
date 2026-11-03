<?php

namespace App\Services\Lunar;

use App\DTO\LunarDateResult;
use App\Support\PositiveModulo;
use Carbon\Carbon;

/**
 * Pengalantaka: 1 sasih = 30 tithi (penanggal 1-15, pangelong 1-15),
 * dan setiap 63 hari terjadi pengunalatri (satu hari menelan dua tithi).
 * Rata-rata panjang sasih 30 x 63/64 = 29,53125 hari.
 */
final class PengalantakaLunarCalculator
{
    private const ANCHOR_DATE = '2026-02-02';

    private const ANCHOR_SERIAL = 15; // Purnama = Penanggal 15

    private const ANCHOR_SASIH_INDEX = 7; // Kawolu, zero based

    private const FIRST_PENGUNALATRI = '2026-02-03';

    private const PENGUNALATRI_INTERVAL = 63;

    // Tilem penutup sasih nampih (Mala) menurut kalenderbali.org/purnamatilem.php.
    // Sasih nampih memakai nama sasih sebelumnya, mis. Sadha -> Mala Sadha -> Kasa.
    private const NAMPIH_TILEM = ['2024-07-06', '2027-06-04', '2030-06-30', '2032-07-07', '2035-06-05'];

    private const NAMPIH_KNOWN_FROM = '2024-01-01';

    private const NAMPIH_KNOWN_UNTIL = '2037-12-31';

    private const SASIH = [
        ['kode' => 'kasa', 'nama' => 'Kasa'],
        ['kode' => 'karo', 'nama' => 'Karo'],
        ['kode' => 'katiga', 'nama' => 'Katiga'],
        ['kode' => 'kapat', 'nama' => 'Kapat'],
        ['kode' => 'kalima', 'nama' => 'Kalima'],
        ['kode' => 'kanem', 'nama' => 'Kanem'],
        ['kode' => 'kapitu', 'nama' => 'Kapitu'],
        ['kode' => 'kawolu', 'nama' => 'Kawolu'],
        ['kode' => 'kasanga', 'nama' => 'Kasanga'],
        ['kode' => 'kadasa', 'nama' => 'Kadasa'],
        ['kode' => 'jiyestha', 'nama' => 'Jiyestha'],
        ['kode' => 'sadha', 'nama' => 'Sadha'],
    ];

    public function calculate(Carbon|string $date): LunarDateResult
    {
        $target = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();
        $absoluteSerial = $this->absoluteSerial($target);
        // Pengunalatri yang menelan Tilem + Penanggal 1 tetap disebut Tilem.
        if (PositiveModulo::positiveModulo($absoluteSerial, 30) === 1 && $this->isPengunalatri($target)) {
            $absoluteSerial--;
        }
        $month = $this->floorDiv($absoluteSerial - 1, 30);
        $serial = PositiveModulo::positiveModulo($absoluteSerial - 1, 30) + 1;

        $nampihMonths = array_map(
            fn (string $tilem): int => $this->floorDiv($this->absoluteSerial(Carbon::parse($tilem)) - 1, 30),
            self::NAMPIH_TILEM,
        );
        $nampihShift = count(array_filter($nampihMonths, fn (int $m): bool => $m > 0 && $m <= $month))
            - count(array_filter($nampihMonths, fn (int $m): bool => $m > $month && $m <= 0));
        $sasih = self::SASIH[PositiveModulo::positiveModulo(self::ANCHOR_SASIH_INDEX + $month - $nampihShift, 12)];
        $nampihKnown = $target->betweenIncluded(self::NAMPIH_KNOWN_FROM, self::NAMPIH_KNOWN_UNTIL);

        return new LunarDateResult(
            available: true,
            verificationStatus: 'calculated',
            tanggalMasehi: $target->toDateString(),
            sasih: $sasih,
            fase: $serial <= 15 ? 'penanggal' : 'pangelong',
            nomorHari: $serial <= 15 ? $serial : $serial - 15,
            isPurnama: $serial === 15,
            isTilem: $serial === 30,
            isNampihSasih: in_array($month, $nampihMonths, true),
            sumber: 'Rumus Pengalantaka internal',
            referensi: 'Anchor KalenderBali.org 2 Februari 2026, siklus pengunalatri 63 hari, dan daftar sasih nampih 2024-2037',
            catatan: $nampihKnown
                ? 'Hasil kalkulasi. Gunakan dataset terverifikasi sebagai keputusan final bila tersedia.'
                : 'Hasil kalkulasi di luar 2024-2037: nama sasih belum memperhitungkan sasih nampih dan dapat bergeser. Fase bulan tetap dihitung.',
        );
    }

    public function isPengunalatri(Carbon|string $date): bool
    {
        $target = $date instanceof Carbon ? $date : Carbon::parse($date);
        $delta = (int) Carbon::parse(self::FIRST_PENGUNALATRI)->diffInDays($target, false);

        return $delta % self::PENGUNALATRI_INTERVAL === 0;
    }

    /**
     * Nomor tithi berjalan relatif terhadap anchor (1-30 = sasih anchor).
     */
    private function absoluteSerial(Carbon $date): int
    {
        $anchor = Carbon::parse(self::ANCHOR_DATE)->startOfDay();
        $dayDelta = (int) $anchor->diffInDays($date, false);

        return self::ANCHOR_SERIAL + $dayDelta + $this->pengunalatriCount($date) - $this->pengunalatriCount($anchor);
    }

    private function pengunalatriCount(Carbon $date): int
    {
        $delta = (int) Carbon::parse(self::FIRST_PENGUNALATRI)->startOfDay()->diffInDays($date, false);

        return $this->floorDiv($delta, self::PENGUNALATRI_INTERVAL);
    }

    private function floorDiv(int $value, int $divisor): int
    {
        return intdiv($value - PositiveModulo::positiveModulo($value, $divisor), $divisor);
    }
}
