<?php

namespace Database\Seeders;

use App\Models\CalendarLunarDate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LunarReference2026Seeder extends Seeder
{
    public function run(): void
    {
        $pairs = [
            ['2026-01-03', '2026-01-18', 'kapitu'], ['2026-02-02', '2026-02-16', 'kawolu'],
            ['2026-03-03', '2026-03-18', 'kasanga'], ['2026-04-02', '2026-04-16', 'kadasa'],
            ['2026-05-01', '2026-05-16', 'jiyestha'], ['2026-05-31', '2026-06-14', 'sadha'],
            ['2026-06-29', '2026-07-14', 'kasa'], ['2026-07-29', '2026-08-12', 'karo'],
            ['2026-08-27', '2026-09-11', 'katiga'], ['2026-09-26', '2026-10-11', 'kapat'],
            ['2026-10-25', '2026-11-09', 'kalima'], ['2026-11-24', '2026-12-09', 'kanem'],
            ['2026-12-23', null, 'kapitu'],
        ];
        $source = 'Kalender Bali Digital — referensi pembanding';
        $reference = 'https://m.kalenderbali.org/purnamatilem.php';

        foreach ($pairs as [$purnama, $tilem, $sasihCode]) {
            $sasihId = DB::table('master_sasih')->where('kode', $sasihCode)->value('id');
            foreach (array_filter([
                [$purnama, 'penanggal', true, false],
                $tilem ? [$tilem, 'pangelong', false, true] : null,
            ]) as [$date, $fase, $isPurnama, $isTilem]) {
                CalendarLunarDate::query()->updateOrCreate(['tanggal_masehi' => $date], [
                    'sasih_id' => $sasihId, 'fase' => $fase, 'nomor_hari' => 15,
                    'is_purnama' => $isPurnama, 'is_tilem' => $isTilem, 'is_nampih_sasih' => false,
                    'sumber' => $source, 'referensi' => $reference,
                    'catatan' => 'Data referensi web; perlu validasi silang kalender PHDI/ahli sebelum verified.',
                    'verification_status' => 'review', 'is_active' => true,
                ]);
            }
        }
    }
}
