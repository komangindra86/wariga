<?php

namespace App\Console\Commands;

use App\Models\CalendarLunarDate;
use App\Services\Admin\AuditLogger;
use App\Services\Lunar\LunarCsvImporter;
use Illuminate\Console\Command;
use Throwable;

final class ImportLunarCalendar extends Command
{
    protected $signature = 'wariga:import-lunar {file : Lokasi file CSV} {--preview : Validasi tanpa menyimpan}';

    protected $description = 'Preview atau impor dataset lunar Bali terverifikasi';

    public function handle(LunarCsvImporter $importer, AuditLogger $audit): int
    {
        try {
            $result = $this->option('preview')
                ? $importer->preview($this->argument('file'))
                : $importer->import($this->argument('file'));
        } catch (Throwable $exception) {
            $this->error('Impor dibatalkan dan transaksi di-rollback: '.$exception->getMessage());

            return self::FAILURE;
        }

        $count = $this->option('preview') ? count($result['valid']) : $result['imported'];
        if (! $this->option('preview')) {
            $audit->record('dataset.imported', CalendarLunarDate::class, null, [
                'filename' => basename($this->argument('file')),
                'dataset_version' => (string) config('wariga.dataset_version'),
                'imported' => $result['imported'],
                'failed_count' => count($result['failed']),
                'duplicate_count' => count($result['duplicates']),
            ]);
        }
        $this->info(($this->option('preview') ? 'Baris valid' : 'Baris diimpor').": {$count}");
        $this->warn('Baris gagal: '.count($result['failed']));
        $this->warn('Duplikat: '.count($result['duplicates']));

        foreach (array_merge($result['failed'], $result['duplicates']) as $failure) {
            $this->line(json_encode($failure, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }
}
