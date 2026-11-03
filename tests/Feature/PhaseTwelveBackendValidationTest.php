<?php

namespace Tests\Feature;

use App\Console\Commands\ValidateWarigaCycle;
use App\Services\Testing\WarigaCycleFixtureService;
use Database\Seeders\BaliCalendarMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

final class PhaseTwelveBackendValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BaliCalendarMasterSeeder::class);
    }

    public function test_cycle_fixture_command_writes_required_210_day_csv(): void
    {
        $relativePath = 'storage/app/testing/test-wariga-cycle-210.csv';
        $absolutePath = base_path($relativePath);
        @unlink($absolutePath);

        try {
            $this->artisan('wariga:generate-cycle-fixture', [
                '--start' => '2026-07-29',
                '--output' => $relativePath,
            ])->assertSuccessful();

            $rows = app(WarigaCycleFixtureService::class)->read($absolutePath);
            $this->assertCount(210, $rows);
            $this->assertSame(WarigaCycleFixtureService::HEADERS, array_keys($rows[0]));
        } finally {
            @unlink($absolutePath);
        }
    }

    public function test_expert_validation_command_returns_failure_for_a_difference(): void
    {
        $service = app(WarigaCycleFixtureService::class);
        $reference = storage_path('app/testing/test-expert-reference.csv');
        $report = storage_path('app/testing/test-cycle-report.json');
        $rows = $service->generate('2026-07-29');
        $rows[0]['sapta_wara'] = 'Nilai Ahli Berbeda';
        $service->write($reference, $rows);

        try {
            $this->artisan('wariga:validate-cycle', [
                'reference' => $reference,
                '--report' => $report,
            ])->assertFailed();

            $payload = json_decode((string) file_get_contents($report), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(209, $payload['matched']);
            $this->assertSame(1, $payload['different']);
            $this->assertSame(0, $payload['missing']);
        } finally {
            @unlink($reference);
            @unlink($report);
        }
    }

    public function test_validation_command_preserves_absolute_paths_across_platforms(): void
    {
        $method = new ReflectionMethod(ValidateWarigaCycle::class, 'absolutePath');
        $command = new ValidateWarigaCycle;

        $this->assertSame('/tmp/wariga-reference.csv', $method->invoke($command, '/tmp/wariga-reference.csv'));
        $this->assertSame('C:\\data\\wariga-reference.csv', $method->invoke($command, 'C:\\data\\wariga-reference.csv'));
        $this->assertSame('\\\\server\\share\\wariga-reference.csv', $method->invoke($command, '\\\\server\\share\\wariga-reference.csv'));
        $this->assertSame(
            base_path('storage/app/testing/reference.csv'),
            $method->invoke($command, 'storage/app/testing/reference.csv'),
        );
    }
}
