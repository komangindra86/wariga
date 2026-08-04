<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPhaseNineTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_view_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'viewer', 'password' => 'secret123']);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect('/admin/dashboard');
        $this->get('/admin/dashboard')->assertOk()->assertSee('Lunar verified');
    }

    public function test_legacy_rule_admin_and_tester_are_retired(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($super)->get('/admin/rules')->assertNotFound();
        $this->actingAs($super)->get('/admin/rule-tester')->assertNotFound();
        $this->actingAs($super)->get('/admin/expert-system/simulator')
            ->assertOk()->assertSee('Simulator Keputusan Sistem Pakar');
    }
}
