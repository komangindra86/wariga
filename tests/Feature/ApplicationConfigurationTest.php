<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
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

    public function test_artisan_can_boot_before_a_fresh_sqlite_database_exists(): void
    {
        $database = storage_path('framework/testing/fresh-install-missing.sqlite');
        @unlink($database);

        $process = new Process(
            [PHP_BINARY, 'artisan', 'about', '--only=environment'],
            base_path(),
            [
                'APP_ENV' => 'testing',
                'CACHE_STORE' => 'array',
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $database,
            ],
        );
        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertFileDoesNotExist($database);
    }
}
