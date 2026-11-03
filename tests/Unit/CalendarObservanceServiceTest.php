<?php

namespace Tests\Unit;

use App\Services\Calendar\CalendarObservanceService;
use Tests\TestCase;

class CalendarObservanceServiceTest extends TestCase
{
    public function test_it_derives_recurring_balinese_observances(): void
    {
        $calendar = [
            'wuku' => ['nama' => 'Landep'],
            'wewaran' => [
                'tri_wara' => ['nama' => 'Kajeng'],
                'panca_wara' => ['nama' => 'Kliwon'],
                'sapta_wara' => ['nama' => 'Saniscara'],
            ],
        ];

        $result = app(CalendarObservanceService::class)->forDate(
            '2026-01-01',
            $calendar,
            ['is_purnama' => true],
        );

        $this->assertContains(
            'Kajeng Kliwon',
            array_column($result['observances'], 'name'),
        );
        $this->assertContains(
            'Tumpek Landep',
            array_column($result['observances'], 'name'),
        );
        $this->assertContains(
            'Purnama',
            array_column($result['observances'], 'name'),
        );
        $this->assertTrue($result['is_public_holiday']);
        $this->assertTrue($result['is_red_date']);
    }

    public function test_it_labels_buda_cemeng_with_its_wuku(): void
    {
        $result = app(CalendarObservanceService::class)->forDate(
            '2026-01-02',
            [
                'wuku' => ['nama' => 'Kelawu'],
                'wewaran' => [
                    'tri_wara' => ['nama' => 'Pasah'],
                    'panca_wara' => ['nama' => 'Wage'],
                    'sapta_wara' => ['nama' => 'Buda'],
                ],
            ],
            [],
        );

        $this->assertSame(
            ['Buda Cemeng Kelawu'],
            array_column($result['observances'], 'name'),
        );
    }

    public function test_saraswati_is_derived_outside_configured_years(): void
    {
        $result = app(CalendarObservanceService::class)->forDate('2027-05-29', [
            'wuku' => ['nama' => 'Watugunung'],
            'wewaran' => [
                'tri_wara' => ['nama' => 'Pasah'],
                'panca_wara' => ['nama' => 'Umanis'],
                'sapta_wara' => ['nama' => 'Saniscara'],
            ],
        ], []);

        $this->assertContains('Hari Raya Saraswati', array_column($result['observances'], 'name'));
        $this->assertTrue($result['is_red_date']);
    }

    public function test_galungan_and_kuningan_are_recurring_red_dates(): void
    {
        $service = app(CalendarObservanceService::class);
        $galungan = $service->forDate('2027-01-01', [
            'wuku' => ['nama' => 'Dungulan'],
            'wewaran' => [
                'tri_wara' => ['nama' => 'Kajeng'],
                'panca_wara' => ['nama' => 'Kliwon'],
                'sapta_wara' => ['nama' => 'Buda'],
            ],
        ], []);
        $kuningan = $service->forDate('2027-01-11', [
            'wuku' => ['nama' => 'Kuningan'],
            'wewaran' => [
                'tri_wara' => ['nama' => 'Pasah'],
                'panca_wara' => ['nama' => 'Kliwon'],
                'sapta_wara' => ['nama' => 'Saniscara'],
            ],
        ], []);

        $this->assertContains(
            'Hari Raya Galungan',
            array_column($galungan['observances'], 'name'),
        );
        $this->assertTrue($galungan['is_red_date']);
        $this->assertContains(
            'Hari Raya Kuningan',
            array_column($kuningan['observances'], 'name'),
        );
        $this->assertTrue($kuningan['is_red_date']);
    }
}
