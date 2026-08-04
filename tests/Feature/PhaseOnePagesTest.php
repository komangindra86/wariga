<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOnePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_available(): void
    {
        foreach (['/', '/privacy-policy', '/terms', '/account-deletion'] as $uri) {
            $this->get($uri)->assertOk();
        }
        $this->get('/')->assertDontSee('Fondasi aplikasi');
        $this->get('/privacy-policy')
            ->assertSee('Data yang diproses')
            ->assertSee('Penghapusan dan kendali pengguna');
        $this->get('/terms')
            ->assertSee('Perbedaan pakem')
            ->assertSee('Pembelajaran pengguna');
    }

    public function test_admin_login_is_available_and_dashboard_is_protected(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Masuk');
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
    }

    public function test_health_endpoint_reports_database_connection_without_secrets(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertExactJsonStructure(['status', 'database', 'timestamp'])
            ->assertJson(['status' => 'ok', 'database' => 'connected'])
            ->assertJsonMissingPath('config')
            ->assertJsonMissingPath('environment');
    }
}
