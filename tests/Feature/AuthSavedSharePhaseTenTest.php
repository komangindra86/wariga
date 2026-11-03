<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\AlaAyuMaster;
use App\Models\SharedDewasa;
use App\Models\User;
use App\Models\WarigaActivity;
use Database\Seeders\BaliCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthSavedSharePhaseTenTest extends TestCase
{
    use RefreshDatabase;

    private WarigaActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BaliCalendarMasterSeeder::class);
        config([
            'wariga.engine_version' => '10.0.0',
            'wariga.rule_version' => '10.0.0',
        ]);
        $this->activity = WarigaActivity::query()->create([
            'kode' => 'PH10', 'nama' => 'Fase 10', 'slug' => 'fase-10',
            'kategori' => 'Test', 'deskripsi' => 'Test', 'disclaimer' => 'Test',
            'is_active' => true,
        ]);
        $master = AlaAyuMaster::query()->create([
            'nama' => 'Saved Knowledge', 'deskripsi' => 'Test',
            'komposisi_teks' => 'Buda', 'komposisi' => [['raw' => 'Buda', 'tokens' => ['Buda']]],
            'sumber_nama' => 'Test', 'sumber_url' => 'https://example.test', 'diimpor_pada' => now(),
        ]);
        DB::table('ala_ayu_activity_rules')->insert([
            'activity_id' => $this->activity->id, 'ala_ayu_master_id' => $master->id,
            'effect' => 'positive', 'source_url' => 'https://example.test',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_saved_result_keeps_versioned_snapshot_and_warns_after_version_change(): void
    {
        $user = User::factory()->create();
        $saved = $this->actingAs($user, 'sanctum')->postJson('/api/v1/user/saved-dewasa', [
            'activity_id' => $this->activity->id, 'date' => '2026-07-29',
            'notes' => 'Hari pilihan keluarga',
        ])->assertCreated()
            ->assertJsonPath('data.engine_version', '10.0.0')
            ->assertJsonPath('data.rule_version', '10.0.0')
            ->assertJsonPath('data.snapshot_outdated', false);

        config(['wariga.rule_version' => '10.1.0']);
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/saved-dewasa/'.$saved->json('data.id'))
            ->assertOk()->assertJsonPath('data.snapshot_outdated', true)
            ->assertJsonPath('data.snapshot_notice', 'Aturan Wariga telah diperbarui sejak hasil ini disimpan.');
    }

    public function test_public_share_counts_views_and_survives_account_deletion(): void
    {
        $user = User::factory()->create();
        $user->createToken('phone');
        $user->createToken('tablet');
        AuditLog::query()->create([
            'user_id' => $user->id, 'action' => 'account.test',
            'entity_type' => User::class, 'entity_id' => $user->id,
            'created_at' => now(),
        ]);
        $savedId = $this->actingAs($user, 'sanctum')->postJson('/api/v1/user/saved-dewasa', [
            'activity_id' => $this->activity->id, 'date' => '2026-07-29',
        ])->json('data.id');
        $token = $this->actingAs($user, 'sanctum')->postJson('/api/v1/dewasa/share', [
            'saved_dewasa_id' => $savedId,
        ])->assertCreated()->json('data.token');

        $this->getJson('/api/v1/shared/'.$token)->assertOk()->assertJsonPath('data.view_count', 1);
        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/user/account')->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.test', 'user_id' => null]);
        $this->assertDatabaseHas('shared_results', ['public_token' => $token, 'created_by' => null]);
        $this->getJson('/api/v1/shared/'.$token)->assertOk()->assertJsonPath('data.view_count', 2);
    }

    public function test_expired_share_is_not_publicly_accessible(): void
    {
        SharedDewasa::query()->create([
            'public_token' => 'expired-token', 'activity_id' => $this->activity->id,
            'tanggal' => '2026-07-29', 'result_snapshot' => [],
            'engine_version' => '10.0.0', 'rule_version' => '10.0.0',
            'expires_at' => now()->subMinute(), 'created_at' => now()->subDay(),
        ]);

        $this->getJson('/api/v1/shared/expired-token')->assertNotFound();
    }
}
