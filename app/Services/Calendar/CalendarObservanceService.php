<?php

namespace App\Services\Calendar;

use App\Contracts\LunarCalendarProviderInterface;
use Carbon\Carbon;

final class CalendarObservanceService
{
    /**
     * Rerainan yang jatuh tetap tiap 210 hari: [sapta wara, panca wara, wuku, nama, tipe].
     * Nama disamakan dengan config wariga.hindu_observances agar tidak dobel.
     */
    private const PAWUKON_RERAINAN = [
        ['Wraspati', 'Wage', 'Sungsang', 'Sugihan Jawa', 'balinese_observance'],
        ['Sukra', 'Kliwon', 'Sungsang', 'Sugihan Bali', 'balinese_observance'],
        ['Redite', 'Paing', 'Dungulan', 'Penyekeban Galungan', 'balinese_observance'],
        ['Soma', 'Pon', 'Dungulan', 'Penyajaan Galungan', 'balinese_observance'],
        ['Anggara', 'Wage', 'Dungulan', 'Hari Penampahan Galungan', 'hindu_holiday'],
        ['Buda', 'Kliwon', 'Dungulan', 'Hari Raya Galungan', 'hindu_holiday'],
        ['Wraspati', 'Umanis', 'Dungulan', 'Hari Umanis Galungan', 'hindu_holiday'],
        ['Saniscara', 'Pon', 'Dungulan', 'Pemaridan Guru', 'balinese_observance'],
        ['Redite', 'Wage', 'Kuningan', 'Ulihan', 'balinese_observance'],
        ['Soma', 'Kliwon', 'Kuningan', 'Pemacekan Agung', 'balinese_observance'],
        ['Sukra', 'Wage', 'Kuningan', 'Hari Penampahan Kuningan', 'hindu_holiday'],
        ['Saniscara', 'Kliwon', 'Kuningan', 'Hari Raya Kuningan', 'hindu_holiday'],
        ['Saniscara', 'Umanis', 'Watugunung', 'Hari Raya Saraswati', 'hindu_holiday'],
        ['Redite', 'Paing', 'Sinta', 'Banyu Pinaruh', 'balinese_observance'],
        ['Soma', 'Pon', 'Sinta', 'Soma Ribek', 'balinese_observance'],
        ['Anggara', 'Wage', 'Sinta', 'Sabuh Mas', 'balinese_observance'],
        ['Buda', 'Kliwon', 'Sinta', 'Hari Raya Pagerwesi', 'hindu_holiday'],
    ];

    public function __construct(private readonly LunarCalendarProviderInterface $lunar) {}

    /**
     * @param  array<string, mixed>  $calendar
     * @param  array<string, mixed>  $lunar
     * @return array{observances: list<array{name: string, type: string}>, is_public_holiday: bool, is_red_date: bool}
     */
    public function forDate(Carbon|string $date, array $calendar, array $lunar): array
    {
        $date = $date instanceof Carbon ? $date->toDateString() : $date;
        $observances = [];
        $add = function (string $name, string $type) use (&$observances): void {
            if (! collect($observances)->contains('name', $name)) {
                $observances[] = ['name' => $name, 'type' => $type];
            }
        };

        foreach (config("wariga.holidays.{$date}", []) as $name) {
            $add($name, 'national_holiday');
        }
        foreach (config("wariga.hindu_observances.{$date}", []) as $name) {
            $add($name, 'hindu_holiday');
        }

        $wewaran = $calendar['wewaran'] ?? [];
        $tri = $wewaran['tri_wara']['nama'] ?? null;
        $panca = $wewaran['panca_wara']['nama'] ?? null;
        $sapta = $wewaran['sapta_wara']['nama'] ?? null;
        $wuku = $calendar['wuku']['nama'] ?? null;

        if ($tri === 'Kajeng' && $panca === 'Kliwon') {
            $add('Kajeng Kliwon', 'balinese_observance');
        }
        if ($sapta === 'Buda' && $panca === 'Wage') {
            $add('Buda Cemeng'.($wuku ? " {$wuku}" : ''), 'balinese_observance');
        }
        if ($sapta === 'Anggara' && $panca === 'Kliwon') {
            $add('Anggara Kasih'.($wuku ? " {$wuku}" : ''), 'balinese_observance');
        }
        if ($sapta === 'Buda' && $panca === 'Kliwon') {
            $add('Buda Kliwon'.($wuku ? " {$wuku}" : ''), 'balinese_observance');
        }
        foreach (self::PAWUKON_RERAINAN as [$raSapta, $raPanca, $raWuku, $name, $type]) {
            if ($sapta === $raSapta && $panca === $raPanca && $wuku === $raWuku) {
                $add($name, $type);
            }
        }

        $tumpek = [
            'Landep' => 'Tumpek Landep',
            'Wariga' => 'Tumpek Wariga',
            'Krulut' => 'Tumpek Krulut',
            'Uye' => 'Tumpek Uye',
            'Wayang' => 'Tumpek Wayang',
        ];
        if ($sapta === 'Saniscara' && $panca === 'Kliwon' && isset($tumpek[$wuku])) {
            $add($tumpek[$wuku], 'balinese_observance');
        }

        foreach ($this->sasihRerainan(Carbon::parse($date), $lunar) as $name) {
            $add($name, 'hindu_holiday');
        }

        if (($lunar['is_purnama'] ?? false) === true) {
            $add('Purnama', 'moon_phase');
        }
        if (($lunar['is_tilem'] ?? false) === true) {
            $add('Tilem', 'moon_phase');
        }

        $types = array_column($observances, 'type');

        return [
            'observances' => $observances,
            'is_public_holiday' => in_array('national_holiday', $types, true),
            'is_red_date' => count(array_intersect($types, ['national_holiday', 'hindu_holiday'])) > 0,
        ];
    }

    /**
     * Rerainan yang mengikuti sasih (Bimas Hindu): Siwa Ratri = purwaning Tilem Kapitu,
     * Tawur Agung = Tilem Kasanga, Nyepi dan Ngembak Geni = 1 dan 2 hari sesudahnya.
     * Dihitung dari hari Tilem, bukan nomor pangelong, agar tetap benar saat pengunalatri.
     *
     * @param  array<string, mixed>  $lunar
     * @return list<string>
     */
    private function sasihRerainan(Carbon $date, array $lunar): array
    {
        if (($lunar['available'] ?? false) !== true) {
            return [];
        }
        $isTilem = function (array $day, string $sasih): bool {
            return ($day['is_tilem'] ?? false) === true
                && ($day['sasih']['kode'] ?? null) === $sasih
                && ($day['is_nampih_sasih'] ?? false) !== true;
        };
        $at = fn (int $offset): array => $this->lunar->getForDate($date->copy()->addDays($offset))->toArray();
        $phase = $lunar['fase'] ?? null;
        $dayNumber = (int) ($lunar['nomor_hari'] ?? 0);
        $names = [];

        if ($phase === 'pangelong' && $dayNumber >= 13 && $dayNumber < 15 && $isTilem($at(1), 'kapitu')) {
            $names[] = 'Hari Suci Siwa Ratri';
        }
        if ($isTilem($lunar, 'kasanga')) {
            $names[] = 'Hari Tawur Agung Kesanga';
        }
        if ($phase === 'penanggal' && $dayNumber <= 3) {
            if ($isTilem($at(-1), 'kasanga')) {
                $names[] = 'Hari Suci Nyepi Tahun Baru Saka '.($date->year - 78);
            } elseif ($isTilem($at(-2), 'kasanga')) {
                $names[] = 'Ngembak Geni';
            }
        }

        return $names;
    }
}
