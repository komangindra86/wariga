<?php

namespace Tests\Feature;

use Database\Seeders\BaliCalendarMasterSeeder;
use Database\Seeders\LunarReference2026Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarMonthPhaseTwentyOneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BaliCalendarMasterSeeder::class);
        $this->seed(LunarReference2026Seeder::class);
        config([
            'wariga.reference_date' => '2026-07-29',
            'wariga.reference_cycle_index' => 0,
        ]);
    }

    public function test_month_endpoint_returns_all_dates_in_one_payload_with_calendar_and_lunar_data(): void
    {
        $this->getJson('/api/v1/calendar/month?year=2026&month=8&per_page=31')
            ->assertOk()
            ->assertJsonCount(31, 'data.items')
            ->assertJsonPath('data.items.0.calendar.tanggal_masehi', '2026-08-01')
            ->assertJsonPath('data.items.11.lunar.is_tilem', true)
            ->assertJsonPath('data.items.26.lunar.is_purnama', true)
            ->assertJsonPath('data.items.16.is_public_holiday', true)
            ->assertJsonPath('data.items.16.observances.0.name', 'Proklamasi Kemerdekaan')
            ->assertJsonPath('data.items.0.calendar.wewaran.tri_wara.nama', 'Pasah')
            ->assertJsonPath('data.items.0.calendar.wewaran.panca_wara.nama', 'Kliwon')
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        '*' => [
                            'calendar' => ['tanggal_masehi', 'wewaran'],
                            'lunar' => ['available', 'verification_status'],
                            'verification_status',
                        ],
                    ],
                    'pagination',
                ],
            ]);
    }

    public function test_hindu_holy_days_and_national_holidays_are_red_dates(): void
    {
        $this->getJson('/api/v1/calendar/month?year=2026&month=6&per_page=31')
            ->assertOk()
            ->assertJsonPath('data.items.16.calendar.tanggal_masehi', '2026-06-17')
            ->assertJsonPath('data.items.16.observances.0.name', 'Hari Raya Galungan')
            ->assertJsonPath('data.items.16.is_red_date', true)
            ->assertJsonPath('data.items.26.observances.0.name', 'Hari Raya Kuningan')
            ->assertJsonPath('data.items.26.is_red_date', true);

        $this->getJson('/api/v1/calendar/month?year=2026&month=8&per_page=31')
            ->assertOk()
            ->assertJsonPath('data.items.16.observances.0.name', 'Proklamasi Kemerdekaan')
            ->assertJsonPath('data.items.16.is_red_date', true);
    }
}
