<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\BaliCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditVersioningPhaseElevenTest extends TestCase
{
    use RefreshDatabase;

    public function test_wariga_response_contains_all_three_versions(): void
    {
        $this->seed(BaliCalendarMasterSeeder::class);
        config([
            'wariga.reference_date' => '2026-01-01',
            'wariga.engine_version' => '11.2.0',
            'wariga.rule_version' => '11.3.0',
            'wariga.dataset_version' => '11.4.0',
        ]);

        $response = $this->getJson('/api/v1/calendar/2026-01-01');

        $response->assertOk()
            ->assertJsonPath('data.engine_version', '11.2.0')
            ->assertJsonPath('data.rule_version', '11.3.0')
            ->assertJsonPath('data.dataset_version', '11.4.0');
    }

    public function test_configuration_changes_are_audited_with_old_and_new_values(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->put('/admin/settings', [
            'engine_version' => '2.0.0',
            'rule_version' => '3.0.0',
            'dataset_version' => '4.0.0',
            'reference_date' => '2026-01-01',
            'reference_cycle_index' => 15,
            'initial_score' => 55,
            'search_cache_ttl' => 600,
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'engine.version_changed',
            'entity_type' => 'engine_settings',
        ]);

        $log = AuditLog::query()->where('action', 'engine.version_changed')->firstOrFail();
        $this->assertSame('engine_version', $log->new_values['key']);
        $this->assertSame('2.0.0', $log->new_values['value']);
    }
}
