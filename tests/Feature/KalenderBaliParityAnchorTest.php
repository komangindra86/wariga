<?php

namespace Tests\Feature;

use App\Services\Calendar\BaliCalendarService;
use Database\Seeders\VerifiedQaCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KalenderBaliParityAnchorTest extends TestCase
{
    use RefreshDatabase;

    public function test_august_30_2026_matches_kalender_bali_reference(): void
    {
        $this->seed(VerifiedQaCalendarMasterSeeder::class);
        config([
            'wariga.reference_date' => '2026-07-29',
            'wariga.reference_cycle_index' => 115,
        ]);

        $result = app(BaliCalendarService::class)->calculate('2026-08-30');

        $this->assertSame('Uye', $result->wuku->nama);
        $this->assertSame('Redite', $result->wewaran['sapta_wara']->nama);
        $this->assertSame('Indra', $result->wewaran['asta_wara']->nama);
    }
}
