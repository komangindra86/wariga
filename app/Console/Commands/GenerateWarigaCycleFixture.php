<?php

namespace App\Console\Commands;

use App\Services\Testing\WarigaCycleFixtureService;
use Illuminate\Console\Command;

final class GenerateWarigaCycleFixture extends Command
{
    protected $signature = 'wariga:generate-cycle-fixture
        {--start= : Tanggal awal YYYY-MM-DD; default memakai reference date}
        {--output=storage/app/testing/wariga-cycle-210.csv : Lokasi file keluaran}';

    protected $description = 'Membuat dataset diagnostik satu siklus Wariga (210 hari)';

    public function handle(WarigaCycleFixtureService $service): int
    {
        $start = (string) ($this->option('start') ?: config('wariga.reference_date'));
        $output = base_path((string) $this->option('output'));
        $rows = $service->generate($start);
        $service->write($output, $rows);

        $this->info("210 baris ditulis ke {$output}.");
        $this->warn('Status formula tetap belum_divalidasi_ahli sampai dibandingkan dengan sumber ahli.');

        return self::SUCCESS;
    }
}
