<?php

namespace App\Services\Astronomy;

/**
 * Posisi Matahari dan Bulan untuk hisab hari raya.
 * Rumus: Jean Meeus, Astronomical Algorithms (2nd ed.), bab 25, 47, dan 49.
 * Seri bulan dipangkas ke suku-suku utama; galat posisi di bawah 0,01 derajat.
 * Semua sudut dalam derajat, waktu dalam Julian Day (UT kecuali disebut TT).
 */
final class Ephemeris
{
    private const SYNODIC_MONTH = 29.530588861;

    // Tabel 47.A: [D, M, M', F, Σl (1e-6 derajat), Σr (1e-3 km)].
    private const MOON_LR = [
        [0, 0, 1, 0, 6288774, -20905355], [2, 0, -1, 0, 1274027, -3699111],
        [2, 0, 0, 0, 658314, -2955968], [0, 0, 2, 0, 213618, -569925],
        [0, 1, 0, 0, -185116, 48888], [0, 0, 0, 2, -114332, -3149],
        [2, 0, -2, 0, 58793, 246158], [2, -1, -1, 0, 57066, -152138],
        [2, 0, 1, 0, 53322, -170733], [2, -1, 0, 0, 45758, -204586],
        [0, 1, -1, 0, -40923, -129620], [1, 0, 0, 0, -34720, 108743],
        [0, 1, 1, 0, -30383, 104755], [2, 0, 0, -2, 15327, 10321],
        [0, 0, 1, 2, -12528, 0], [0, 0, 1, -2, 10980, 79661],
        [4, 0, -1, 0, 10675, -34782], [0, 0, 3, 0, 10034, -23210],
        [4, 0, -2, 0, 8548, -21636], [2, 1, -1, 0, -7888, 24208],
        [2, 1, 0, 0, -6766, 30824], [1, 0, -1, 0, -5163, -8379],
        [1, 1, 0, 0, 4987, -16675], [2, -1, 1, 0, 4036, -12831],
        [2, 0, 2, 0, 3994, -10445], [4, 0, 0, 0, 3861, -11650],
        [2, 0, -3, 0, 3665, 14403], [0, 1, -2, 0, -2689, -7003],
        [2, 0, -1, 2, -2602, 0], [2, -1, -2, 0, 2390, 10056],
        [1, 0, 1, 0, -2348, 6322], [2, -2, 0, 0, 2236, -9884],
        [0, 1, 2, 0, -2120, 5751], [0, 2, 0, 0, -2069, 0],
        [2, -2, -1, 0, 2048, -4950], [2, 0, 1, -2, -1773, 4130],
        [2, 0, 0, 2, -1595, 0], [4, -1, -1, 0, 1215, -3958],
        [0, 0, 2, 2, -1110, 0], [3, 0, -1, 0, -892, 3258],
        [2, 1, 1, 0, -810, 2616], [4, -1, -2, 0, 759, -1897],
        [0, 2, -1, 0, -713, -2117], [2, 2, -1, 0, -700, 2354],
        [2, 1, -2, 0, 691, 0], [2, -1, 0, -2, 596, 0],
        [4, 0, 1, 0, 549, -1423], [0, 0, 4, 0, 537, -1117],
        [4, -1, 0, 0, 520, -1571], [1, 0, -2, 0, -487, -1739],
        [2, 1, 0, -2, -399, 0], [0, 0, 2, -2, -381, -4421],
        [1, 1, 1, 0, 351, 0], [3, 0, -2, 0, -340, 0],
        [4, 0, -3, 0, 330, 0], [2, -1, 2, 0, 327, 0],
        [0, 2, 1, 0, -323, 1165], [1, 1, -1, 0, 299, 0],
        [2, 0, 3, 0, 294, 0], [2, 0, -1, -2, 0, 8752],
    ];

    // Tabel 47.B: [D, M, M', F, Σb (1e-6 derajat)].
    private const MOON_B = [
        [0, 0, 0, 1, 5128122], [0, 0, 1, 1, 280602], [0, 0, 1, -1, 277693],
        [2, 0, 0, -1, 173237], [2, 0, -1, 1, 55413], [2, 0, -1, -1, 46271],
        [2, 0, 0, 1, 32573], [0, 0, 2, 1, 17198], [2, 0, 1, -1, 9266],
        [0, 0, 2, -1, 8822], [2, -1, 0, -1, 8216], [2, 0, -2, -1, 4324],
        [2, 0, 1, 1, 4200], [2, 1, 0, -1, -3359], [2, -1, -1, 1, 2463],
        [2, -1, 0, 1, 2211], [2, -1, -1, -1, 2065], [0, 1, -1, -1, -1870],
        [4, 0, -1, -1, 1828], [0, 1, 0, 1, -1794], [0, 0, 0, 3, -1749],
        [0, 1, -1, 1, -1565], [1, 0, 0, 1, -1491], [0, 1, 1, 1, -1475],
        [0, 1, 1, -1, -1410], [0, 1, 0, -1, -1344], [1, 0, 0, -1, -1335],
        [0, 0, 3, 1, 1107], [4, 0, 0, -1, 1021], [4, 0, -1, 1, 833],
    ];

    /**
     * Saat bulan baru (atau purnama) ke-k sejak 6 Januari 2000, dalam JD UT (bab 49).
     */
    public static function moonPhase(int $k, bool $full = false): float
    {
        $kk = $k + ($full ? 0.5 : 0.0);
        $t = $kk / 1236.85;
        $jde = 2451550.09766 + self::SYNODIC_MONTH * $kk + 0.00015437 * $t ** 2 - 0.00000015 * $t ** 3 + 0.00000000073 * $t ** 4;
        $e = 1 - 0.002516 * $t - 0.0000074 * $t ** 2;
        $m = deg2rad(2.5534 + 29.1053567 * $kk - 0.0000014 * $t ** 2 - 0.00000011 * $t ** 3);
        $mp = deg2rad(201.5643 + 385.81693528 * $kk + 0.0107582 * $t ** 2 + 0.00001238 * $t ** 3 - 0.000000058 * $t ** 4);
        $f = deg2rad(160.7108 + 390.67050284 * $kk - 0.0016118 * $t ** 2 - 0.00000227 * $t ** 3 + 0.000000011 * $t ** 4);
        $om = deg2rad(124.7746 - 1.56375588 * $kk + 0.0020672 * $t ** 2 + 0.00000215 * $t ** 3);

        $c = $full
            ? -0.40614 * sin($mp) + 0.17302 * $e * sin($m) + 0.01614 * sin(2 * $mp) + 0.01043 * sin(2 * $f)
              + 0.00734 * $e * sin($mp - $m) - 0.00515 * $e * sin($mp + $m) + 0.00209 * $e * $e * sin(2 * $m)
            : -0.40720 * sin($mp) + 0.17241 * $e * sin($m) + 0.01608 * sin(2 * $mp) + 0.01039 * sin(2 * $f)
              + 0.00739 * $e * sin($mp - $m) - 0.00514 * $e * sin($mp + $m) + 0.00208 * $e * $e * sin(2 * $m);
        $c += -0.00111 * sin($mp - 2 * $f) - 0.00057 * sin($mp + 2 * $f) + 0.00056 * $e * sin(2 * $mp + $m)
            - 0.00042 * sin(3 * $mp) + 0.00042 * $e * sin($m + 2 * $f) + 0.00038 * $e * sin($m - 2 * $f)
            - 0.00024 * $e * sin(2 * $mp - $m) - 0.00017 * sin($om) - 0.00007 * sin($mp + 2 * $m)
            + 0.00004 * sin(2 * $mp - 2 * $f) + 0.00004 * sin(3 * $m) + 0.00003 * sin($mp + $m - 2 * $f)
            + 0.00003 * sin(2 * $mp + 2 * $f) - 0.00003 * sin($mp + $m + 2 * $f) + 0.00003 * sin($mp - $m + 2 * $f)
            - 0.00002 * sin($mp - $m - 2 * $f) - 0.00002 * sin(3 * $mp + $m) + 0.00002 * sin(4 * $mp);

        $jde += $c;

        return $jde - self::deltaT($jde) / 86400;
    }

    /**
     * Nomor lunasi k untuk bulan baru terdekat sebelum JD tertentu.
     */
    public static function lunationBefore(float $jd): int
    {
        $k = (int) floor(($jd - 2451550.09766) / self::SYNODIC_MONTH);
        while (self::moonPhase($k + 1) <= $jd) {
            $k++;
        }
        while (self::moonPhase($k) > $jd) {
            $k--;
        }

        return $k;
    }

    /**
     * Posisi ekuatorial geosentris Matahari: [ra, dec] (bab 25, presisi rendah).
     *
     * @return array{0: float, 1: float}
     */
    public static function sun(float $jd): array
    {
        $t = self::centuries($jd);
        $l0 = 280.46646 + 36000.76983 * $t + 0.0003032 * $t * $t;
        $m = deg2rad(357.52911 + 35999.05029 * $t - 0.0001537 * $t * $t);
        $c = (1.914602 - 0.004817 * $t - 0.000014 * $t * $t) * sin($m)
            + (0.019993 - 0.000101 * $t) * sin(2 * $m) + 0.000289 * sin(3 * $m);
        $omega = deg2rad(125.04 - 1934.136 * $t);
        $lambda = $l0 + $c - 0.00569 - 0.00478 * sin($omega);

        return self::toEquatorial($lambda, 0.0, $t);
    }

    /**
     * Posisi ekuatorial geosentris Bulan: [ra, dec, jarak km] (bab 47).
     *
     * @return array{0: float, 1: float, 2: float}
     */
    public static function moon(float $jd): array
    {
        $t = self::centuries($jd);
        $lp = 218.3164477 + 481267.88123421 * $t - 0.0015786 * $t ** 2 + $t ** 3 / 538841 - $t ** 4 / 65194000;
        $d = 297.8501921 + 445267.1114034 * $t - 0.0018819 * $t ** 2 + $t ** 3 / 545868 - $t ** 4 / 113065000;
        $m = 357.5291092 + 35999.0502909 * $t - 0.0001536 * $t ** 2 + $t ** 3 / 24490000;
        $mp = 134.9633964 + 477198.8675055 * $t + 0.0087414 * $t ** 2 + $t ** 3 / 69699 - $t ** 4 / 14712000;
        $f = 93.272095 + 483202.0175233 * $t - 0.0036539 * $t ** 2 - $t ** 3 / 3526000 + $t ** 4 / 863310000;
        $a1 = 119.75 + 131.849 * $t;
        $a2 = 53.09 + 479264.29 * $t;
        $a3 = 313.45 + 481266.484 * $t;
        $e = 1 - 0.002516 * $t - 0.0000074 * $t ** 2;

        $sl = 3958 * self::sind($a1) + 1962 * self::sind($lp - $f) + 318 * self::sind($a2);
        $sr = 0.0;
        foreach (self::MOON_LR as [$cd, $cm, $cmp, $cf, $l, $r]) {
            $arg = $cd * $d + $cm * $m + $cmp * $mp + $cf * $f;
            $factor = $e ** abs($cm);
            $sl += $l * $factor * self::sind($arg);
            $sr += $r * $factor * self::cosd($arg);
        }
        $sb = -2235 * self::sind($lp) + 382 * self::sind($a3) + 175 * self::sind($a1 - $f)
            + 175 * self::sind($a1 + $f) + 127 * self::sind($lp - $mp) - 115 * self::sind($lp + $mp);
        foreach (self::MOON_B as [$cd, $cm, $cmp, $cf, $b]) {
            $sb += $b * $e ** abs($cm) * self::sind($cd * $d + $cm * $m + $cmp * $mp + $cf * $f);
        }

        $nutation = -0.00478 * self::sind(125.04 - 1934.136 * $t);
        [$ra, $dec] = self::toEquatorial($lp + $sl / 1e6 + $nutation, $sb / 1e6, $t);

        return [$ra, $dec, 385000.56 + $sr / 1000];
    }

    /**
     * Ketinggian geometris (tanpa refraksi) sebuah benda di lokasi pengamat.
     */
    public static function altitude(float $ra, float $dec, float $jd, float $latitude, float $longitude): float
    {
        $t = ($jd - 2451545.0) / 36525;
        $gmst = 280.46061837 + 360.98564736629 * ($jd - 2451545.0) + 0.000387933 * $t * $t - $t ** 3 / 38710000;
        $hourAngle = $gmst + $longitude - $ra;

        return rad2deg(asin(self::sind($latitude) * self::sind($dec) + self::cosd($latitude) * self::cosd($dec) * self::cosd($hourAngle)));
    }

    /**
     * Jarak sudut (elongasi) geosentris Bulan-Matahari.
     */
    public static function elongation(float $jd): float
    {
        [$sunRa, $sunDec] = self::sun($jd);
        [$moonRa, $moonDec] = self::moon($jd);

        return rad2deg(acos(min(1, self::sind($sunDec) * self::sind($moonDec) + self::cosd($sunDec) * self::cosd($moonDec) * self::cosd($sunRa - $moonRa))));
    }

    /**
     * Ketinggian toposentris pusat Bulan (dikoreksi paralaks, tanpa refraksi).
     */
    public static function moonAltitude(float $jd, float $latitude, float $longitude): float
    {
        [$ra, $dec, $distance] = self::moon($jd);
        $geocentric = self::altitude($ra, $dec, $jd, $latitude, $longitude);
        $parallax = rad2deg(asin(6378.14 / $distance));

        return $geocentric - $parallax * self::cosd($geocentric);
    }

    /**
     * Saat Matahari terbenam (piringan atas di ufuk, refraksi standar) pada tanggal lokal.
     */
    public static function sunset(string $date, float $latitude, float $longitude, float $utcOffsetHours): float
    {
        $noon = self::julianDay($date) + 0.5 - $utcOffsetHours / 24;
        $low = $noon + 3 / 24;
        $high = $noon + 9 / 24;
        $target = -0.8333;
        for ($i = 0; $i < 40; $i++) {
            $mid = ($low + $high) / 2;
            [$ra, $dec] = self::sun($mid);
            if (self::altitude($ra, $dec, $mid, $latitude, $longitude) > $target) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }

        return ($low + $high) / 2;
    }

    /**
     * Julian Day pukul 00:00 UT untuk tanggal Y-m-d (kalender Gregorian).
     */
    public static function julianDay(string $date): float
    {
        [$y, $m, $d] = array_map('intval', explode('-', $date));
        if ($m <= 2) {
            $y--;
            $m += 12;
        }
        $a = intdiv($y, 100);

        return floor(365.25 * ($y + 4716)) + floor(30.6001 * ($m + 1)) + $d + 2 - $a + intdiv($a, 4) - 1524.5;
    }

    /**
     * Tanggal lokal (Y-m-d) dari JD UT dengan selisih zona waktu tertentu.
     */
    public static function localDate(float $jd, float $utcOffsetHours): string
    {
        $z = (int) floor($jd + 0.5 + $utcOffsetHours / 24);
        $alpha = (int) floor(($z - 1867216.25) / 36524.25);
        $a = $z + 1 + $alpha - intdiv($alpha, 4);
        $b = $a + 1524;
        $c = (int) floor(($b - 122.1) / 365.25);
        $d = (int) floor(365.25 * $c);
        $e = (int) floor(($b - $d) / 30.6001);
        $day = $b - $d - (int) floor(30.6001 * $e);
        $month = $e < 14 ? $e - 1 : $e - 13;
        $year = $month > 2 ? $c - 4716 : $c - 4715;

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * ΔT = TT - UT dalam detik (Espenak & Meeus, 2005-2150).
     */
    private static function deltaT(float $jd): float
    {
        $year = 2000 + ($jd - 2451545.0) / 365.25;
        $t = $year - 2000;
        if ($year < 2005) {
            return 63.86 + 0.3345 * $t - 0.060374 * $t ** 2 + 0.0017275 * $t ** 3 + 0.000651814 * $t ** 4 + 0.00002373599 * $t ** 5;
        }
        if ($year < 2050) {
            return 62.92 + 0.32217 * $t + 0.005589 * $t ** 2;
        }

        return -20 + 32 * (($year - 1820) / 100) ** 2 - 0.5628 * (2150 - $year);
    }

    private static function centuries(float $jdUt): float
    {
        return ($jdUt + self::deltaT($jdUt) / 86400 - 2451545.0) / 36525;
    }

    /**
     * @return array{0: float, 1: float}
     */
    private static function toEquatorial(float $lambda, float $beta, float $t): array
    {
        $epsilon = 23.4392911 - 0.0130042 * $t + 0.00256 * self::cosd(125.04 - 1934.136 * $t);
        $ra = rad2deg(atan2(self::sind($lambda) * self::cosd($epsilon) - tan(deg2rad($beta)) * self::sind($epsilon), self::cosd($lambda)));
        $dec = rad2deg(asin(self::sind($beta) * self::cosd($epsilon) + self::cosd($beta) * self::sind($epsilon) * self::sind($lambda)));

        return [fmod($ra + 360, 360), $dec];
    }

    private static function sind(float $degrees): float
    {
        return sin(deg2rad($degrees));
    }

    private static function cosd(float $degrees): float
    {
        return cos(deg2rad($degrees));
    }
}
