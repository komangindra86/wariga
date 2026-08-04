<?php

namespace App\Console\Commands;

use App\Models\AlaAyuMaster;
use App\Services\Wariga\KalenderBaliReferenceImporter;
use Illuminate\Console\Command;
use Throwable;

class SyncKalenderBaliReference extends Command
{
    protected $signature = 'wariga:sync-kalenderbali
        {--master-only : Hanya sinkronkan master Ala-Ayuning Dewasa}
        {--current-only : Sinkronkan hanya 10 kegiatan yang tersedia di aplikasi}';

    protected $description = 'Sinkronkan master dan definisi aturan dari referensi KalenderBali.org';

    public function handle(KalenderBaliReferenceImporter $importer): int
    {
        try {
            $result = $importer->importMaster();
        } catch (Throwable $exception) {
            if (! $this->option('current-only') || AlaAyuMaster::query()->count() < 100) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }
            $this->warn('Sumber master sementara gagal dibaca; memakai master lokal terakhir.');
            $result = ['imported' => AlaAyuMaster::query()->count()];
        }

        $this->info("{$result['imported']} master Ala-Ayuning Dewasa berhasil disinkronkan.");

        if (! $this->option('master-only')) {
            try {
                $activities = $this->option('current-only')
                    ? $importer->importCurrentActivityRules()
                    : $importer->importActivityRules();
            } catch (Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            $this->info(
                "{$activities['activities']} kegiatan dan {$activities['relations']} relasi aturan berhasil disinkronkan.",
            );
            if ($activities['missing_rules'] !== []) {
                $this->warn(
                    'Master yang belum ditemukan: '.implode(', ', $activities['missing_rules']),
                );
            }
            if ($activities['failed_pages'] !== []) {
                $this->warn(
                    count($activities['failed_pages']).' halaman kegiatan gagal dibaca dari sumber.',
                );
                foreach ($activities['failed_pages'] as $url) {
                    $this->line(" - {$url}");
                }
            }
        }

        return self::SUCCESS;
    }
}
