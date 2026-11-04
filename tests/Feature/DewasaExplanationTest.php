<?php

namespace Tests\Feature;

use App\Models\AlaAyuMaster;
use App\Models\WarigaActivity;
use Database\Seeders\BaliCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DewasaExplanationTest extends TestCase
{
    use RefreshDatabase;

    public function test_explains_why_pangelong_prohibition_beats_wuku_and_rerainan(): void
    {
        $this->seed(BaliCalendarMasterSeeder::class);
        $activity = WarigaActivity::query()->create([
            'kode' => 'MLS-TEST', 'nama' => 'Melaspas', 'slug' => 'melaspas-test', 'kategori' => 'Dewa Yadnya',
            'deskripsi' => 'Pengujian.', 'disclaimer' => 'Pengujian.', 'urutan' => 1, 'is_active' => true,
        ]);
        // 04-11-2026: Buda Kliwon Sinta (Pagerwesi), Pangelong 10 sasih Kalima.
        $this->rule($activity, 'Geheng Manyinget', 'negative', ['Buda', 'Pangelong', '10'],
            'Tidak baik untuk segala pekerjaan yang penting-penting termasuk melakukan yadnya karena banyak ganguan.');
        $this->rule($activity, 'Buda Kliwon Sinta Uji', 'positive', ['Buda', 'Kliwon', 'Sinta'], 'Baik untuk upacara.');

        $result = $this->getJson('/api/v1/dewasa/melaspas-test/2026-11-04')
            ->assertOk()
            ->json('data.recommendations.0');

        $this->assertSame('avoid', $result['status']);
        $this->assertSame('Sebaiknya dihindari untuk Melaspas karena ada Geheng Manyinget.', $result['explanation']['summary']);
        $points = implode(' ', $result['explanation']['points']);
        $this->assertStringContainsString('Geheng Manyinget (Buda Pangelong 10) jatuh pada tanggal ini. Tidak baik untuk segala pekerjaan', $points);
        $this->assertStringContainsString('tetapi kalah kuat: Geheng Manyinget dihitung dari penanggal/pangelong', $points);
        $this->assertStringContainsString('hanya dari wuku (pekan Bali)', $points);
        $this->assertStringContainsString('Hari ini juga Hari Raya Pagerwesi', $points);
        // Unsur terkuat tampil lebih dulu dan alasannya memakai deskripsi asli.
        $this->assertSame('Geheng Manyinget', $result['main_reasons'][0]['rule_name']);
        $this->assertStringStartsWith('Tidak baik untuk segala pekerjaan', $result['main_reasons'][0]['reason']);
    }

    private function rule(WarigaActivity $activity, string $name, string $effect, array $tokens, string $description): void
    {
        $master = AlaAyuMaster::query()->create([
            'nama' => $name, 'deskripsi' => $description, 'komposisi_teks' => implode(' ', $tokens),
            'komposisi' => [['raw' => implode(' ', $tokens), 'tokens' => $tokens]],
            'sumber_nama' => 'Sumber pengujian', 'sumber_url' => 'https://example.test', 'diimpor_pada' => now(),
        ]);
        DB::table('ala_ayu_activity_rules')->insert([
            'activity_id' => $activity->id, 'ala_ayu_master_id' => $master->id, 'effect' => $effect,
            'source_url' => 'https://example.test', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
