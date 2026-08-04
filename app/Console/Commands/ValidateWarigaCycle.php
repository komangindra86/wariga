<?php

namespace App\Console\Commands;

use App\Services\Testing\WarigaCycleFixtureService;
use Illuminate\Console\Command;

final class ValidateWarigaCycle extends Command
{
    protected $signature = 'wariga:validate-cycle
        {reference : CSV referensi ahli}
        {--start= : Tanggal awal; default memakai tanggal pertama referensi}
        {--report=storage/app/testing/wariga-cycle-validation.json : Lokasi laporan JSON}';

    protected $description = 'Membandingkan hasil sistem dengan dataset referensi ahli';

    public function handle(WarigaCycleFixtureService $service): int
    {
        $referencePath = $this->absolutePath((string) $this->argument('reference'));
        $expertRows = $service->read($referencePath);
        $start = (string) ($this->option('start') ?: ($expertRows[0]['tanggal'] ?? ''));
        $comparison = $service->compare($service->generate($start), $expertRows);
        $reportPath = $this->absolutePath((string) $this->option('report'));

        if (! is_dir(dirname($reportPath))) {
            mkdir(dirname($reportPath), 0775, true);
        }

        file_put_contents(
            $reportPath,
            json_encode($comparison, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );

        $this->table(
            ['Cocok', 'Berbeda', 'Referensi tidak tersedia'],
            [[$comparison['matched'], $comparison['different'], $comparison['missing']]],
        );
        $this->line("Laporan: {$reportPath}");

        return $comparison['different'] === 0 && $comparison['missing'] === 0
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function absolutePath(string $path): string
    {
        return preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1 ? $path : base_path($path);
    }
}
