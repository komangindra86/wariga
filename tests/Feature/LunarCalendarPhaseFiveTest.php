<?php

namespace Tests\Feature;

use App\Models\CalendarLunarDate;
use App\Services\Lunar\LunarCsvImporter;
use Database\Seeders\BaliCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class LunarCalendarPhaseFiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BaliCalendarMasterSeeder::class);
    }

    public function test_valid_csv_can_be_previewed_and_imported(): void
    {
        $path = $this->csv([$this->row('2026-07-29', 'kasa', 'penanggal', 1)]);
        $importer = app(LunarCsvImporter::class);
        $preview = $importer->preview($path);

        $this->assertCount(1, $preview['valid']);
        $this->assertSame([], $preview['failed']);
        $this->assertSame(1, $importer->import($path)['imported']);
        $this->assertDatabaseHas('calendar_lunar_dates', ['tanggal_masehi' => '2026-07-29']);
    }

    public function test_invalid_rows_are_reported_and_not_imported(): void
    {
        $result = app(LunarCsvImporter::class)->import(
            $this->csv([$this->row('2026-02-30', 'tidak-ada', 'salah', 20)])
        );

        $this->assertSame(0, $result['imported']);
        $this->assertCount(1, $result['failed']);
        $this->assertDatabaseCount('calendar_lunar_dates', 0);
    }

    public function test_duplicate_dates_in_file_and_database_are_reported(): void
    {
        $importer = app(LunarCsvImporter::class);
        $importer->import($this->csv([$this->row('2026-07-29', 'kasa', 'penanggal', 1)]));
        $preview = $importer->preview($this->csv([
            $this->row('2026-07-29', 'kasa', 'penanggal', 1),
            $this->row('2026-07-30', 'kasa', 'penanggal', 2),
            $this->row('2026-07-30', 'kasa', 'penanggal', 2),
        ]));

        $this->assertCount(2, $preview['duplicates']);
        $this->assertSame(['database', 'dalam_file'], array_column($preview['duplicates'], 'jenis'));
    }

    public function test_fatal_import_error_rolls_back_every_insert(): void
    {
        $path = $this->csv([
            $this->row('2026-08-01', 'kasa', 'penanggal', 4),
            $this->row('2026-08-02', 'kasa', 'penanggal', 5),
        ]);
        $insertCount = 0;

        DB::listen(function ($query) use (&$insertCount): void {
            if (str_contains($query->sql, 'insert into') && str_contains($query->sql, 'calendar_lunar_dates')) {
                if (++$insertCount === 2) {
                    throw new RuntimeException('Simulasi kegagalan fatal.');
                }
            }
        });

        $this->expectException(RuntimeException::class);
        try {
            app(LunarCsvImporter::class)->import($path);
        } finally {
            $this->assertDatabaseCount('calendar_lunar_dates', 0);
        }
    }

    public function test_api_returns_verified_and_draft_data_with_honest_status(): void
    {
        $sasihId = DB::table('master_sasih')->where('kode', 'kasa')->value('id');

        foreach ([['2026-08-10', 'verified'], ['2026-08-11', 'draft']] as [$date, $status]) {
            CalendarLunarDate::query()->create([
                'tanggal_masehi' => $date, 'sasih_id' => $sasihId,
                'fase' => 'penanggal', 'nomor_hari' => 10,
                'sumber' => 'Lembaga uji', 'referensi' => 'Dokumen uji',
                'verification_status' => $status, 'is_active' => true,
            ]);

            $this->getJson("/api/calendar/lunar/{$date}")
                ->assertOk()
                ->assertJson([
                    'available' => true, 'tanggal_masehi' => $date,
                    'verification_status' => $status,
                    'sasih' => ['kode' => 'kasa', 'nama' => 'Kasa'],
                ]);
        }
    }

    public function test_api_labels_formula_data_as_calculated_for_an_unknown_date(): void
    {
        $this->getJson('/api/calendar/lunar/2030-01-01')->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('verification_status', 'calculated')
            ->assertJsonPath('sumber', 'Rumus Pengalantaka internal')
            ->assertJsonStructure(['fase', 'nomor_hari', 'sasih' => ['kode', 'nama']]);
    }

    /** @param list<list<string|int>> $rows */
    private function csv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lunar-').'.csv';
        $handle = fopen($path, 'wb');
        fputcsv($handle, LunarCsvImporter::HEADERS);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return $path;
    }

    /** @return list<string|int> */
    private function row(string $date, string $sasih, string $fase, int $day): array
    {
        return [$date, $sasih, $fase, $day, 0, 0, 0, 'Lembaga uji', 'Dokumen uji', '', 'verified'];
    }
}
