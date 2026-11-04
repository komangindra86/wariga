<?php

namespace App\Services\Calendar;

use App\Services\Astronomy\Ephemeris;
use Carbon\Carbon;

/**
 * Libur nasional (SKB 3 Menteri) yang dihitung, bukan didaftar manual:
 * tanggal tetap, Paskah (komputus Gregorian), Imlek dan Waisak (fase bulan),
 * serta hari besar Islam (hisab imkanur rukyat MABIMS seperti taqwim Kemenag).
 * Cuti bersama dan pergeseran libur adalah keputusan pemerintah, jadi tidak dihitung.
 */
final class NationalHolidayCalculator
{
    // Titik paling barat Indonesia tempat hilal paling mudah memenuhi kriteria.
    private const HILAL_LATITUDE = 5.5483;

    private const HILAL_LONGITUDE = 95.3238;

    private const WIB = 7;

    // Lunasi Meeus k = 0 (6 Januari 2000) adalah awal Syawal 1420 H.
    private const HIJRI_INDEX_OFFSET = 17037;

    // MABIMS baru (tinggi 3°, elongasi 6,4°) dipakai Kemenag sejak awal 2022.
    private const MABIMS_2021_FROM = '2022-03-01';

    /** @var array<int, array<string, list<string>>> */
    private static array $years = [];

    /**
     * @return list<string>
     */
    public function forDate(string $date): array
    {
        return $this->forYear((int) substr($date, 0, 4))[$date] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function forYear(int $year): array
    {
        if (isset(self::$years[$year])) {
            return self::$years[$year];
        }
        $holidays = [];
        $add = function (string $date, string $name) use (&$holidays, $year): void {
            if ((int) substr($date, 0, 4) === $year) {
                $holidays[$date][] = $name;
            }
        };

        $add("{$year}-01-01", "Tahun Baru {$year} Masehi");
        $add("{$year}-05-01", 'Hari Buruh Internasional');
        $add("{$year}-06-01", 'Hari Lahir Pancasila');
        $add("{$year}-08-17", 'Proklamasi Kemerdekaan');
        $add("{$year}-12-25", 'Kelahiran Yesus Kristus');

        $easter = $this->easter($year);
        $add($easter->copy()->subDays(2)->toDateString(), 'Wafat Yesus Kristus');
        $add($easter->toDateString(), 'Kebangkitan Yesus Kristus (Paskah)');
        $add($easter->copy()->addDays(39)->toDateString(), 'Kenaikan Yesus Kristus');

        $add($this->chineseNewYear($year), 'Tahun Baru Imlek '.($year + 551).' Kongzili');
        $add($this->vesak($year), 'Hari Raya Waisak '.($year + 544).' BE');

        $k = Ephemeris::lunationBefore(Ephemeris::julianDay("{$year}-01-01"));
        for ($i = -2; $i <= 13; $i++) {
            $index = $k + $i + self::HIJRI_INDEX_OFFSET;
            $hijriYear = intdiv($index, 12) + 1;
            $start = Carbon::parse($this->hijriMonthStart($k + $i));
            match ($index % 12) {
                0 => $add($start->toDateString(), "1 Muharam Tahun Baru Islam {$hijriYear} Hijriah"),
                2 => $add($start->copy()->addDays(11)->toDateString(), 'Maulid Nabi Muhammad S.A.W.'),
                6 => $add($start->copy()->addDays(26)->toDateString(), 'Isra Mikraj Nabi Muhammad S.A.W.'),
                9 => [
                    $add($start->toDateString(), "Idul Fitri {$hijriYear} Hijriah"),
                    $add($start->copy()->addDay()->toDateString(), "Idul Fitri {$hijriYear} Hijriah"),
                ],
                11 => $add($start->copy()->addDays(9)->toDateString(), "Idul Adha {$hijriYear} Hijriah"),
                default => null,
            };
        }
        ksort($holidays);

        return self::$years[$year] = $holidays;
    }

    /**
     * Awal bulan Hijriah dari konjungsi ke-k: besok bila hilal memenuhi kriteria
     * saat Matahari terbenam pada hari konjungsi, selain itu lusa (istikmal).
     */
    public function hijriMonthStart(int $k): string
    {
        $conjunction = Ephemeris::moonPhase($k);
        $day = Ephemeris::localDate($conjunction, self::WIB);
        $sunset = Ephemeris::sunset($day, self::HILAL_LATITUDE, self::HILAL_LONGITUDE, self::WIB);
        [$ra, $dec] = Ephemeris::moon($sunset);
        $altitude = Ephemeris::altitude($ra, $dec, $sunset, self::HILAL_LATITUDE, self::HILAL_LONGITUDE);
        $elongation = Ephemeris::elongation($sunset);
        $ageHours = ($sunset - $conjunction) * 24;

        $visible = $conjunction < $sunset && ($day >= self::MABIMS_2021_FROM
            ? $altitude >= 3 && $elongation >= 6.4
            : $altitude >= 2 && ($elongation >= 3 || $ageHours >= 8));

        return Carbon::parse($day)->addDays($visible ? 1 : 2)->toDateString();
    }

    /**
     * Minggu Paskah Gregorian (algoritma Meeus/Jones/Butcher).
     */
    private function easter(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $h = (19 * $a + $b - intdiv($b, 4) - intdiv($b - intdiv($b + 8, 25) + 1, 3) + 15) % 30;
        $l = (32 + 2 * ($b % 4) + 2 * intdiv($c, 4) - $h - $c % 4) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = ($h + $l - 7 * $m + 114) % 31 + 1;

        return Carbon::create($year, $month, $day);
    }

    /**
     * Imlek: bulan baru pertama (waktu Beijing) pada 21 Januari - 20 Februari.
     */
    private function chineseNewYear(int $year): string
    {
        $k = Ephemeris::lunationBefore(Ephemeris::julianDay("{$year}-01-21"));
        do {
            $date = Ephemeris::localDate(Ephemeris::moonPhase($k++), 8);
        } while ($date < "{$year}-01-21");

        return $date;
    }

    /**
     * Waisak (Walubi): purnama yang saatnya (UT) jatuh 6 Mei - 4 Juni, tanggal WIB.
     */
    private function vesak(int $year): string
    {
        $k = Ephemeris::lunationBefore(Ephemeris::julianDay("{$year}-05-06")) - 1;
        do {
            $fullMoon = Ephemeris::moonPhase($k++, true);
        } while (Ephemeris::localDate($fullMoon, 0) < "{$year}-05-06");

        return Ephemeris::localDate($fullMoon, self::WIB);
    }
}
