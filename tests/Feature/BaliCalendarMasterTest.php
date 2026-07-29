<?php

namespace Tests\Feature;

use App\Repositories\CalendarMasterRepository;
use Database\Seeders\BaliCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BaliCalendarMasterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $tables = [
        'master_eka_wara',
        'master_dwi_wara',
        'master_tri_wara',
        'master_catur_wara',
        'master_panca_wara',
        'master_sad_wara',
        'master_sapta_wara',
        'master_asta_wara',
        'master_sanga_wara',
        'master_dasa_wara',
        'master_wuku',
        'master_sasih',
        'master_ingkel',
    ];

    public function test_all_calendar_master_tables_exist_with_required_columns(): void
    {
        foreach ($this->tables as $table) {
            $this->assertTrue(Schema::hasColumns($table, [
                'id',
                'kode',
                'nama',
                'nama_indonesia',
                'nama_alternatif',
                'urutan',
                'urip',
                'deskripsi',
                'metadata',
                'verification_status',
                'is_active',
                'created_at',
                'updated_at',
            ]), "Struktur tabel {$table} tidak lengkap.");
        }
    }

    public function test_seeder_populates_required_master_data_as_draft(): void
    {
        $this->seed(BaliCalendarMasterSeeder::class);

        $expectedCounts = [
            'master_eka_wara' => 1,
            'master_dwi_wara' => 2,
            'master_tri_wara' => 3,
            'master_catur_wara' => 4,
            'master_panca_wara' => 5,
            'master_sad_wara' => 6,
            'master_sapta_wara' => 7,
            'master_asta_wara' => 8,
            'master_sanga_wara' => 9,
            'master_dasa_wara' => 10,
            'master_wuku' => 30,
        ];

        foreach ($expectedCounts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
            $this->assertSame(
                DB::table($table)->count(),
                DB::table($table)->where('verification_status', 'draft')->count(),
            );
        }
    }

    public function test_master_codes_are_unique(): void
    {
        $this->seed(BaliCalendarMasterSeeder::class);

        foreach (array_slice($this->tables, 0, 11) as $table) {
            $duplicateCount = DB::table($table)
                ->select('kode')
                ->groupBy('kode')
                ->havingRaw('COUNT(*) > 1')
                ->count();

            $this->assertSame(0, $duplicateCount, "Kode duplikat ditemukan pada {$table}.");
        }
    }

    public function test_seeder_is_safe_to_run_more_than_once(): void
    {
        $this->seed(BaliCalendarMasterSeeder::class);
        $this->seed(BaliCalendarMasterSeeder::class);

        $this->assertDatabaseCount('master_eka_wara', 1);
        $this->assertDatabaseCount('master_dasa_wara', 10);
        $this->assertDatabaseCount('master_wuku', 30);
    }

    public function test_repository_reads_by_code_order_active_and_verified_status(): void
    {
        $this->seed(BaliCalendarMasterSeeder::class);
        DB::table('master_sapta_wara')->where('kode', 'soma')->update([
            'verification_status' => 'verified',
        ]);
        DB::table('master_sapta_wara')->where('kode', 'anggara')->update([
            'is_active' => false,
        ]);

        $repository = app(CalendarMasterRepository::class);

        $this->assertSame('Soma', $repository->findByCode('sapta_wara', 'soma')?->nama);
        $this->assertSame('Redite', $repository->findByOrder('master_sapta_wara', 1)?->nama);
        $this->assertCount(6, $repository->allActive('sapta_wara'));
        $this->assertSame(['soma'], $repository->allVerified('sapta_wara')->pluck('kode')->all());
    }
}
