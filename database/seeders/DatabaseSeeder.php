<?php

namespace Database\Seeders;

use App\Models\AlaAyuMaster;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(BaliCalendarMasterSeeder::class);
        $this->call(WarigaActivitySeeder::class);
        $this->call(AdminUserSeeder::class);
        $this->call(VerifiedQaCalendarMasterSeeder::class);
        $this->call(LunarReference2026Seeder::class);
        // Master Ala Ayu berasal dari proses sinkronisasi sumber. Relasi hanya
        // dapat disemai setelah master tersebut tersedia.
        if (AlaAyuMaster::query()->exists()) {
            $this->call(KalenderBaliCurrentActivityRuleSeeder::class);
            $this->call(KalenderBaliValidationSeeder::class);
        }
    }
}
