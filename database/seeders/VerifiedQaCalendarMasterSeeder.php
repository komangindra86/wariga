<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VerifiedQaCalendarMasterSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(BaliCalendarMasterSeeder::class);

        foreach ([
            'master_eka_wara', 'master_dwi_wara', 'master_tri_wara',
            'master_catur_wara', 'master_panca_wara', 'master_sad_wara',
            'master_sapta_wara', 'master_asta_wara', 'master_sanga_wara',
            'master_dasa_wara', 'master_wuku', 'master_sasih',
        ] as $table) {
            DB::table($table)->update([
                'verification_status' => 'verified',
                'updated_at' => now(),
            ]);
        }
    }
}
