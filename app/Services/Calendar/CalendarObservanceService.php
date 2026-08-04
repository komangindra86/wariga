<?php

namespace App\Services\Calendar;

use Carbon\Carbon;

final class CalendarObservanceService
{
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
        if ($sapta === 'Buda' && $panca === 'Kliwon' && $wuku === 'Dungulan') {
            $add('Hari Raya Galungan', 'hindu_holiday');
        }
        if ($sapta === 'Saniscara' && $panca === 'Kliwon' && $wuku === 'Kuningan') {
            $add('Hari Raya Kuningan', 'hindu_holiday');
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
}
