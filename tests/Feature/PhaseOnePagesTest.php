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
    }

    public function test_admin_placeholder_pages_are_available(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Login belum tersedia');
        $this->get('/admin/dashboard')->assertOk()->assertSee('Placeholder panel admin');
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
