<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Epoch pawukon kini konstanta di CycleCalculator; nilai lama dari panel admin dibuang.
     */
    public function up(): void
    {
        DB::table('engine_settings')
            ->whereIn('key', ['reference_date', 'reference_cycle_index'])
            ->delete();
    }

    public function down(): void
    {
        // Tidak dipulihkan: epoch tidak lagi dibaca dari engine_settings.
    }
};
