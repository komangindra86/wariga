<?php

namespace Tests\Feature;

use App\Models\AlaAyuMaster;
use App\Models\User;
use App\Models\WarigaActivity;
use Carbon\Carbon;
use Database\Seeders\BaliCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiPhaseEightTest extends TestCase
{
    use RefreshDatabase;

    private WarigaActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BaliCalendarMasterSeeder::class);
        config(['wariga.reference_date' => '2026-07-29', 'wariga.reference_cycle_index' => 0]);
        $this->activity = WarigaActivity::query()->create([
            'kode' => 'API', 'nama' => 'API Test', 'slug' => 'api-test', 'kategori' => 'Test',
            'deskripsi' => 'Test', 'disclaimer' => 'Test', 'is_active' => true,
        ]);
        $master = AlaAyuMaster::query()->create([
            'nama' => 'API Knowledge', 'deskripsi' => 'Test',
            'komposisi_teks' => 'Buda', 'komposisi' => [['raw' => 'Buda', 'tokens' => ['Buda']]],
            'sumber_nama' => 'Test', 'sumber_url' => 'https://example.test', 'diimpor_pada' => now(),
        ]);
        DB::table('ala_ayu_activity_rules')->insert([
            'activity_id' => $this->activity->id, 'ala_ayu_master_id' => $master->id,
            'effect' => 'positive', 'source_url' => 'https://example.test',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_public_endpoints_use_envelope_and_metadata(): void
    {
        $this->getJson('/api/v1/activities')->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.items.0.code', 'API');
        $this->getJson('/api/v1/calendar/2026-07-29')->assertOk()->assertJsonStructure(['data' => ['engine_version', 'rule_version', 'verification_status']]);
        $this->getJson('/api/v1/calendar/upcoming?days=3')->assertOk()->assertJsonStructure(['data' => ['items', 'engine_version']]);
        $this->getJson('/api/v1/dewasa/api-test/2026-07-29')->assertOk()->assertJsonPath('data.recommendations.0.date', '2026-07-29');
        $this->getJson('/api/v1/calendar/not-a-date')->assertNotFound();
    }

    public function test_daily_recommendation_is_generated_automatically_for_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-29 08:00:00', config('wariga.timezone')));

        $this->getJson('/api/v1/dewasa/today')
            ->assertOk()
            ->assertJsonPath('data.date', '2026-07-29')
            ->assertJsonStructure(['data' => [
                'recommended',
                'avoid',
                'calendar_basis' => ['saptawara', 'pancawara', 'wuku', 'sasih'],
                'verification_status',
            ]]);

        Carbon::setTestNow();
    }

    public function test_activity_catalog_can_return_more_than_one_hundred_items(): void
    {
        $rows = [];
        foreach (range(1, 100) as $number) {
            $rows[] = [
                'kode' => "CAT-{$number}", 'nama' => "Kegiatan {$number}",
                'slug' => "kegiatan-{$number}", 'kategori' => 'Katalog',
                'deskripsi' => 'Kegiatan katalog', 'disclaimer' => 'Panduan',
                'urutan' => $number, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ];
        }
        WarigaActivity::query()->insert($rows);

        $this->getJson('/api/v1/activities?per_page=250')->assertOk()
            ->assertJsonCount(101, 'data.items')
            ->assertJsonPath('data.pagination.total', 101);
    }

    public function test_register_login_user_and_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Made', 'email' => 'made@example.test', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertCreated();
        $token = $response->json('data.token');
        $this->withToken($token)->getJson('/api/v1/user')->assertOk()->assertJsonPath('data.email', 'made@example.test');
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->withToken($token)->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_saved_dewasa_is_private_and_can_be_shared(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $saved = $this->actingAs($user, 'sanctum')->postJson('/api/v1/user/saved-dewasa', [
            'activity_id' => $this->activity->id, 'date' => '2026-07-29', 'title' => 'Pilihan',
        ])->assertCreated();
        $id = $saved->json('data.id');
        $this->actingAs($other, 'sanctum')->getJson("/api/v1/user/saved-dewasa/{$id}")->assertNotFound();
        $share = $this->actingAs($user, 'sanctum')->postJson('/api/v1/dewasa/share', ['saved_dewasa_id' => $id])->assertCreated();
        $this->getJson('/api/v1/shared/'.$share->json('data.token'))->assertOk()->assertJsonPath('success', true);
    }

    public function test_validation_error_contract(): void
    {
        $this->getJson('/api/v1/dewasa/search')->assertStatus(422)
            ->assertJsonPath('success', false)->assertJsonPath('error_code', 'VALIDATION_ERROR');
    }

    public function test_anonymous_contribution_enters_review_queue(): void
    {
        $this->postJson('/api/v1/contributions', [
            'type' => 'wariga_knowledge',
            'is_anonymous' => true,
            'subject' => 'Koreksi istilah rahina',
            'message' => 'Mohon meninjau kembali istilah rahina berdasarkan sumber terlampir.',
            'region' => 'Gianyar',
            'tradition' => 'Pakem keluarga',
            'source_reference' => 'Catatan lontar milik keluarga',
            'consent' => true,
        ])->assertCreated()
            ->assertJsonPath('data.status', 'pending_review')
            ->assertJsonPath('data.status_label', 'Menunggu tinjauan');

        $this->assertDatabaseHas('user_contributions', [
            'type' => 'wariga_knowledge', 'is_anonymous' => true,
            'contact_email' => null, 'status' => 'pending_review',
        ]);
    }

    public function test_authenticated_user_feedback_requires_consent_and_updates_duplicate_submission(): void
    {
        $user = User::factory()->create();
        $payload = [
            'activity_id' => $this->activity->id,
            'recommendation_date' => '2026-07-29',
            'rating' => 'not_helpful',
            'reason' => 'Tanggal lain dipilih setelah konsultasi.',
            'region' => 'Badung',
            'tradition' => 'Pakem keluarga',
            'consulted_expert' => true,
            'expert_role' => 'Pemangku',
        ];

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/dewasa/feedback', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR');

        $firstResponse = $this->actingAs($user, 'sanctum')->postJson('/api/v1/dewasa/feedback', array_merge($payload, [
            'learning_consent' => true,
        ]))->assertCreated()->assertJsonPath('data.updated', false);
        $feedbackId = $firstResponse->json('data.id');

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/dewasa/feedback', array_merge($payload, [
            'learning_consent' => true,
            'rating' => 'helpful',
        ]))->assertOk()
            ->assertJsonPath('data.id', $feedbackId)
            ->assertJsonPath('data.updated', true);

        $this->assertDatabaseCount('dewasa_user_feedback', 1);
        $this->assertDatabaseHas('dewasa_user_feedback', [
            'user_id' => $user->id,
            'rating' => 'helpful',
            'submission_count' => 2,
            'learning_consent' => true,
        ]);

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/user/account')->assertOk();
        $this->assertDatabaseCount('dewasa_user_feedback', 0);
    }
}
