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

    // Sasih nampih (Mala) mengikuti siklus Meton 19 tahun: 7 nampih per siklus,
    // diletakkan sesudah Jiyestha atau Sadha. Kunci = (tahun - 2005) mod 19.
    // Cocok dengan kalenderbali.org/purnamatilem.php untuk 2003-2045.
    // Sasih nampih memakai nama sasih sebelumnya, mis. Sadha -> Mala Sadha -> Kasa.
    private const NAMPIH_CYCLE_BASE_YEAR = 2005;

    private const NAMPIH_CYCLE = [0 => 'sadha', 3 => 'jiyestha', 6 => 'sadha', 8 => 'sadha', 11 => 'jiyestha', 14 => 'sadha', 17 => 'jiyestha'];

    // Sebelum 2003 penempatan nampih belum baku (mis. Nampih Katiga 2001).
    private const NAMPIH_RULE_FROM_YEAR = 2003;

    private const MONTH_TABLE_PAST = 1500;

    private const MONTH_TABLE_FUTURE = 1500;

    /** @var array<int, array{sasih: int, nampih: bool}>|null */
    private static ?array $months = null;

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

        $info = self::monthTable()[$month] ?? null;
        $sasih = self::SASIH[$info['sasih'] ?? PositiveModulo::positiveModulo(self::ANCHOR_SASIH_INDEX + $month, 12)];
        $nampihKnown = $info !== null && $target->year >= self::NAMPIH_RULE_FROM_YEAR;

        return new LunarDateResult(
            available: true,
            verificationStatus: 'calculated',
            tanggalMasehi: $target->toDateString(),
            sasih: $sasih,
            fase: $serial <= 15 ? 'penanggal' : 'pangelong',
            nomorHari: $serial <= 15 ? $serial : $serial - 15,
            isPurnama: $serial === 15,
            isTilem: $serial === 30,
            isNampihSasih: $info['nampih'] ?? false,
            sumber: 'Rumus Pengalantaka internal',
            referensi: 'Anchor KalenderBali.org 2 Februari 2026, siklus pengunalatri 63 hari, dan siklus nampih Meton 19 tahun',
            catatan: $nampihKnown
                ? 'Hasil kalkulasi. Gunakan dataset terverifikasi sebagai keputusan final bila tersedia.'
                : 'Hasil kalkulasi sebelum 2003: penempatan sasih nampih saat itu belum baku sehingga nama sasih dapat bergeser. Fase bulan tetap dihitung.',
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

    /**
     * Nama sasih dan status nampih tiap sasih, berjalan maju dan mundur dari sasih anchor.
     * Indeks 0 = sasih anchor; sasih m berisi tithi berjalan 30m+1 sampai 30m+30.
     *
     * @return array<int, array{sasih: int, nampih: bool}>
     */
    private static function monthTable(): array
    {
        if (self::$months !== null) {
            return self::$months;
        }
        $months = [0 => ['sasih' => self::ANCHOR_SASIH_INDEX, 'nampih' => false]];

        for ($m = 0; $m < self::MONTH_TABLE_FUTURE; $m++) {
            $current = $months[$m];
            $isLeapAfter = ! $current['nampih']
                && self::nampihSasihOf(self::tilemYear($m)) === self::SASIH[$current['sasih']]['kode'];
            $months[$m + 1] = $isLeapAfter
                ? ['sasih' => $current['sasih'], 'nampih' => true]
                : ['sasih' => ($current['sasih'] + 1) % 12, 'nampih' => false];
        }
        for ($m = 0; $m > -self::MONTH_TABLE_PAST; $m--) {
            $current = $months[$m];
            $previous = ($current['sasih'] + 11) % 12;
            $months[$m - 1] = $current['nampih']
                ? ['sasih' => $current['sasih'], 'nampih' => false]
                : ['sasih' => $previous, 'nampih' => self::nampihSasihOf(self::tilemYear($m - 1)) === self::SASIH[$previous]['kode']];
        }

        return self::$months = $months;
    }

    private static function nampihSasihOf(int $year): ?string
    {
        if ($year < self::NAMPIH_RULE_FROM_YEAR) {
            return null;
        }

        return self::NAMPIH_CYCLE[PositiveModulo::positiveModulo($year - self::NAMPIH_CYCLE_BASE_YEAR, 19)] ?? null;
    }

    /**
     * Tahun Masehi (perkiraan) tilem penutup sasih m. Cukup untuk Jiyestha/Sadha
     * yang selalu jatuh Mei-Juli, jauh dari pergantian tahun.
     */
    private static function tilemYear(int $month): int
    {
        $days = (int) round((30 * ($month + 1) - self::ANCHOR_SERIAL) * self::PENGUNALATRI_INTERVAL / (self::PENGUNALATRI_INTERVAL + 1));

        return (int) gmdate('Y', strtotime(self::ANCHOR_DATE.' UTC') + $days * 86400);
    }

    private function floorDiv(int $value, int $divisor): int
    {
        return intdiv($value - PositiveModulo::positiveModulo($value, $divisor), $divisor);
    }
}
