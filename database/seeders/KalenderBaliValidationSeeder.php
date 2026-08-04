<?php

namespace Database\Seeders;

use App\Models\WarigaActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KalenderBaliValidationSeeder extends Seeder
{
    public function run(): void
    {
        $ngaben = WarigaActivity::query()->where('kode', 'NGB')->firstOrFail();
        foreach ([
            7 => [],
            8 => ['2026-08-30'],
        ] as $month => $dates) {
            DB::table('kalender_bali_validation_months')->updateOrInsert(
                ['activity_id' => $ngaben->id, 'year' => 2026, 'month' => $month],
                [
                    'recommended_dates' => json_encode($dates),
                    'source_url' => 'https://kalenderbali.org/pilihdewasa.php',
                    'validated_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
