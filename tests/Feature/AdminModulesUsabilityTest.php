<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WarigaActivity;
use App\Models\CalendarLunarDate;
use Database\Seeders\BaliCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminModulesUsabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_modules_are_real_pages_instead_of_placeholders(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        WarigaActivity::query()->create([
            'kode' => 'TEST', 'nama' => 'Kegiatan Test', 'slug' => 'kegiatan-test',
            'kategori' => 'QA', 'deskripsi' => 'QA', 'disclaimer' => 'QA', 'is_active' => true,
        ]);

        $this->actingAs($admin)->get('/admin/activities')->assertOk()->assertSee('Kegiatan Test');
        $this->actingAs($admin)->get('/admin/import')->assertOk()->assertSee('Import Data Lunar');
        $this->actingAs($admin)->get('/admin/expert-system')->assertOk()->assertSee('Sistem Pakar');
        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertSee('Konfigurasi Engine');
    }

    public function test_lunar_control_page_exposes_validation_and_nampih_monitoring(): void
    {
        $this->seed(BaliCalendarMasterSeeder::class);
        CalendarLunarDate::query()->create([
            'tanggal_masehi' => '2026-02-02',
            'sasih_id' => DB::table('master_sasih')->where('kode', 'kawolu')->value('id'),
            'fase' => 'penanggal', 'nomor_hari' => 15, 'is_purnama' => true,
            'is_tilem' => false, 'is_nampih_sasih' => false, 'sumber' => 'QA',
            'referensi' => 'https://example.test', 'catatan' => 'Data pengujian',
            'verification_status' => 'verified', 'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->get('/admin/lunar?year=2026')
            ->assertOk()
            ->assertSee('Validasi rumus tahun 2026')
            ->assertSee('Sinkronkan referensi pembanding')
            ->assertSee('Pengecualian Nampih Sasih')
            ->assertSee('tidak berafiliasi dengan KalenderBali.org');

        $this->actingAs($admin)->post('/admin/lunar/validate', ['year' => 2026])
            ->assertRedirect('/admin/lunar?year=2026')
            ->assertSessionHas('validation_result', fn (array $result): bool => $result['checked'] > 0);
    }

    public function test_super_admin_can_update_engine_settings(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->put('/admin/settings', [
            'engine_version' => '0.3.0', 'rule_version' => '2.0.0',
            'initial_score' => 55, 'search_cache_ttl' => 600,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('0.3.0', json_decode(DB::table('engine_settings')->where('key', 'engine_version')->value('value')));
        $this->assertSame(55, json_decode(DB::table('engine_settings')->where('key', 'initial_score')->value('value')));
    }
}
