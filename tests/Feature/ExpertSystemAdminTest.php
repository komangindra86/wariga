<?php

namespace Tests\Feature;

use App\Models\AlaAyuMaster;
use App\Models\User;
use App\Models\WarigaActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\DewasaUserFeedback;
use Tests\TestCase;

class ExpertSystemAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_manages_knowledge_without_technical_json_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin_data']);
        $activity = $this->activity();
        $master = $this->master();

        $this->actingAs($admin)
            ->get('/admin/expert-system/knowledge?activity_id='.$activity->id)
            ->assertOk()
            ->assertSee('Basis Pengetahuan Wariga')
            ->assertSee('Anda tidak perlu mengisi JSON');

        $this->actingAs($admin)->post('/admin/expert-system/mappings', [
            'activity_id' => $activity->id,
            'ala_ayu_master_id' => $master->id,
            'effect' => 'positive',
            'alahing_priority' => 4,
            'source_url' => 'https://kalenderbali.org/pilihdewasa.php',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('ala_ayu_activity_rules', [
            'activity_id' => $activity->id,
            'ala_ayu_master_id' => $master->id,
            'effect' => 'positive',
        ]);
    }

    public function test_learning_creates_reviewable_proposal_but_does_not_change_rules(): void
    {
        $validator = User::factory()->create(['role' => 'validator_wariga']);
        $activity = $this->activity();

        $this->actingAs($validator)->post('/admin/expert-system/cases', [
            'activity_id' => $activity->id,
            'case_date' => '2026-08-30',
            'expected_status' => 'recommended',
            'predicted_status' => 'consideration',
            'predicted_score' => 50,
            'source_name' => 'KalenderBali.org',
            'source_url' => 'https://kalenderbali.org/pilihdewasa.php',
            'validator_note' => 'Tanggal dipilih sumber.',
        ])->assertRedirect();

        $this->actingAs($validator)
            ->post('/admin/expert-system/proposals/generate')
            ->assertRedirect()
            ->assertSessionHas('success');

        $proposal = DB::table('expert_learning_proposals')->first();
        $this->assertNotNull($proposal);
        $this->assertSame('review_positive', $proposal->proposal_type);
        $this->assertDatabaseCount('ala_ayu_activity_rules', 0);

        $this->actingAs($validator)->post('/admin/expert-system/proposals/'.$proposal->id.'/transition', [
            'status' => 'approved',
            'review_note' => 'Disetujui sebagai bahan koreksi basis pengetahuan.',
        ])->assertRedirect();

        $this->assertDatabaseHas('expert_learning_proposals', ['id' => $proposal->id, 'status' => 'approved']);
        $this->assertDatabaseCount('ala_ayu_activity_rules', 0);
    }

    public function test_expert_simulator_replaces_legacy_rule_tester(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $activity = $this->activity();
        $master = $this->master();
        DB::table('ala_ayu_activity_rules')->insert([
            'activity_id' => $activity->id,
            'ala_ayu_master_id' => $master->id,
            'effect' => 'positive',
            'source_url' => 'https://kalenderbali.org/pilihdewasa.php',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/expert-system/simulator')
            ->assertOk()->assertSee('Simulator Keputusan Sistem Pakar');
        $this->actingAs($admin)->get('/admin/rule-tester')->assertNotFound();
        $this->actingAs($admin)->get('/admin/rules')->assertNotFound();
    }

    public function test_user_feedback_only_creates_review_proposal_after_minimum_sample(): void
    {
        $activity = $this->activity();
        foreach (range(1, 5) as $index) {
            $user = User::factory()->create();
            DewasaUserFeedback::query()->create([
                'user_id' => $user->id,
                'activity_id' => $activity->id,
                'recommendation_date' => "2026-08-0{$index}",
                'rating' => 'not_helpful',
                'predicted_status' => 'recommended',
                'predicted_score' => 60,
                'learning_consent' => true,
                'consented_at' => now(),
            ]);
        }

        $count = app(\App\Services\Wariga\ExpertLearningService::class)->generateProposals();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseHas('expert_learning_proposals', [
            'activity_id' => $activity->id,
            'title' => 'Tinjau pola masukan pengguna',
            'sample_size' => 5,
            'status' => 'suggested',
        ]);
    }

    private function activity(): WarigaActivity
    {
        return WarigaActivity::query()->create([
            'kode' => 'NGB', 'nama' => 'Ngaben', 'slug' => 'ngaben',
            'kategori' => 'Upacara', 'deskripsi' => 'Pitra Yadnya',
            'disclaimer' => 'Perlu sulinggih', 'is_active' => true,
        ]);
    }

    private function master(): AlaAyuMaster
    {
        return AlaAyuMaster::query()->create([
            'nama' => 'Kala Buingrau',
            'deskripsi' => 'Baik untuk Pitra Yadnya.',
            'komposisi_teks' => 'Redite Indra',
            'komposisi' => [['raw' => 'Redite Indra', 'tokens' => ['Redite', 'Indra']]],
            'sumber_nama' => 'KalenderBali.org',
            'sumber_url' => 'https://kalenderbali.org/referensialaayu.php',
            'diimpor_pada' => now(),
        ]);
    }
}
