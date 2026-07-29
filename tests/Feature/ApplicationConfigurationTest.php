<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationConfigurationTest extends TestCase
{
    public function test_application_identity_and_localization_are_configured(): void
    {
        $this->assertSame('Wariga Bali Santih', config('app.name'));
        $this->assertSame('id', config('app.locale'));
        $this->assertSame('id', config('app.fallback_locale'));
        $this->assertSame('Asia/Makassar', config('app.timezone'));
        $this->assertSame('Asia/Makassar', date_default_timezone_get());
        $this->assertSame('+08:00', config('database.connections.mysql.timezone'));
    }
}
