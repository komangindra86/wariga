<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use stdClass;

class CalendarMasterRepository
{
    /**
     * @var list<string>
     */
    private const TABLES = [
        'master_eka_wara',
        'master_dwi_wara',
        'master_tri_wara',
        'master_catur_wara',
        'master_panca_wara',
        'master_sad_wara',
        'master_sapta_wara',
        'master_asta_wara',
        'master_sanga_wara',
        'master_dasa_wara',
        'master_wuku',
        'master_sasih',
        'master_ingkel',
    ];

    public function findByCode(string $master, string $code): ?stdClass
    {
        return DB::table($this->table($master))
            ->where('kode', $code)
            ->first();
    }

    public function findByOrder(string $master, int $order): ?stdClass
    {
        return DB::table($this->table($master))
            ->where('urutan', $order)
            ->first();
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function allActive(string $master): Collection
    {
        return DB::table($this->table($master))
            ->where('is_active', true)
            ->orderBy('urutan')
            ->get();
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function allVerified(string $master): Collection
    {
        return DB::table($this->table($master))
            ->where('verification_status', 'verified')
            ->where('is_active', true)
            ->orderBy('urutan')
            ->get();
    }

    private function table(string $master): string
    {
        $table = str_starts_with($master, 'master_') ? $master : "master_{$master}";

        if (! in_array($table, self::TABLES, true)) {
            throw new InvalidArgumentException("Master Kalender Bali [{$master}] tidak dikenal.");
        }

        return $table;
    }
}
