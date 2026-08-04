<?php

namespace App\Console\Commands;

use App\Services\Lunar\PengalantakaLunarCalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateLunarFormula extends Command
{
    protected $signature = 'wariga:validate-lunar-formula {year=2026}';

    protected $description = 'Bandingkan rumus Pengalantaka internal dengan dataset lunar terverifikasi';

    public function handle(PengalantakaLunarCalculator $calculator): int
    {
        $year = (int) $this->argument('year');
        $rows = DB::table('calendar_lunar_dates as lunar')
            ->join('master_sasih as sasih', 'sasih.id', '=', 'lunar.sasih_id')
            ->whereYear('lunar.tanggal_masehi', $year)
            ->where('lunar.verification_status', 'verified')
            ->orderBy('lunar.tanggal_masehi')
            ->get([
                'lunar.tanggal_masehi', 'lunar.fase', 'lunar.nomor_hari',
                'lunar.is_purnama', 'lunar.is_tilem', 'sasih.kode as sasih_kode',
            ]);
        $differences = [];
        foreach ($rows as $row) {
            $actual = $calculator->calculate(Carbon::parse($row->tanggal_masehi))->toArray();
            $expected = [
                'fase' => $row->fase,
                'nomor_hari' => (int) $row->nomor_hari,
                'is_purnama' => (bool) $row->is_purnama,
                'is_tilem' => (bool) $row->is_tilem,
                'sasih' => $row->sasih_kode,
            ];
            $computed = [
                'fase' => $actual['fase'],
                'nomor_hari' => $actual['nomor_hari'],
                'is_purnama' => $actual['is_purnama'],
                'is_tilem' => $actual['is_tilem'],
                'sasih' => $actual['sasih']['kode'],
            ];
            if ($expected !== $computed) {
                $differences[] = [$row->tanggal_masehi, json_encode($expected), json_encode($computed)];
            }
        }

        $this->info("Diperiksa: {$rows->count()} tanggal; cocok: ".($rows->count() - count($differences)).'; berbeda: '.count($differences));
        if ($differences !== []) {
            $this->table(['Tanggal', 'Dataset', 'Rumus'], array_slice($differences, 0, 30));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
