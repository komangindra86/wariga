<?php

namespace App\Console\Commands;

use App\Services\Lunar\KalenderBaliLunarSyncService;
use Illuminate\Console\Command;

class SyncKalenderBaliLunar extends Command
{
    protected $signature = 'wariga:sync-kalender-bali-lunar {year=2026}';

    protected $description = 'Sinkronkan fakta lunar harian dari KalenderBali.org secara atomik';

    public function handle(KalenderBaliLunarSyncService $service): int
    {
        $year = (int) $this->argument('year');
        $this->info("Mengambil data lunar harian {$year}...");
        $result = $service->syncYear($year, function (int $done, int $total, int $failed): void {
            $this->line("Progres {$done}/{$total}; gagal sementara {$failed}");
        });
        if ($result['failed'] !== []) {
            $this->error('Sinkronisasi dibatalkan; tanggal gagal: '.implode(', ', $result['failed']));

            return self::FAILURE;
        }
        $this->info("{$result['imported']} tanggal berhasil disinkronkan dan diverifikasi.");

        return self::SUCCESS;
    }
}
