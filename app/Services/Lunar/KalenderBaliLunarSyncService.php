<?php

namespace App\Services\Lunar;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class KalenderBaliLunarSyncService
{
    private const SOURCE = 'Kalender Bali Digital — referensi pembanding';
    private const BASE_URL = 'https://kalenderbali.org/index.php';
    private const MOBILE_URL = 'https://m.kalenderbali.org/index.php';

    /** @return array{year:int, imported:int, failed:list<string>} */
    public function syncYear(int $year, ?callable $progress = null): array
    {
        if ($year < 1600 || $year > 3000) {
            throw new RuntimeException('Tahun sinkronisasi harus antara 1600 dan 3000.');
        }

        $dates = collect(CarbonPeriod::create("{$year}-01-01", "{$year}-12-31"))
            ->map(fn (Carbon $date): string => $date->toDateString())
            ->values();
        $rows = [];
        $failed = [];

        foreach ($dates as $index => $date) {
            try {
                $rows[$date] = $this->fetchDate($date);
            } catch (\Throwable) {
                $failed[] = $date;
            }
            if ($progress !== null && (($index + 1) % 25 === 0 || $index + 1 === $dates->count())) {
                $progress($index + 1, $dates->count(), count($failed));
            }
            if (($index + 1) % 100 === 0) {
                sleep(10);
            }
            usleep(150_000);
        }

        for ($pass = 1; $pass <= 3 && $failed !== []; $pass++) {
            sleep(5);
            $retryDates = $failed;
            $failed = [];
            foreach ($retryDates as $date) {
                try {
                    $rows[$date] = $this->fetchDate($date);
                } catch (\Throwable) {
                    $failed[] = $date;
                }
                usleep(500_000);
            }
            if ($progress !== null) {
                $progress($dates->count() - count($failed), $dates->count(), count($failed));
            }
        }

        [$rows, $failed] = $this->repairConsecutiveGaps($rows, $failed);
        if ($failed !== [] || count($rows) !== $dates->count()) {
            return ['year' => $year, 'imported' => 0, 'failed' => $failed];
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                DB::table('calendar_lunar_dates')->updateOrInsert(
                    ['tanggal_masehi' => $row['tanggal_masehi']],
                    $row + ['created_at' => now(), 'updated_at' => now()],
                );
            }
        });
        Cache::clear();

        return ['year' => $year, 'imported' => count($rows), 'failed' => []];
    }

    /** @return array<string,mixed> */
    private function fetchDate(string $date): array
    {
        $parsed = Carbon::parse($date);
        $response = Http::withHeaders([
            'User-Agent' => 'WarigaBaliSantih/1.0 lunar-reference-sync',
        ])->timeout(25)->get(self::BASE_URL, [
            'bulan' => $parsed->month,
            'tanggal' => $parsed->day,
            'tahun' => $parsed->year,
        ]);
        if (! $response->successful()) {
            $response = Http::withHeaders([
                'User-Agent' => 'WarigaBaliSantih/1.0 lunar-reference-sync',
            ])->timeout(25)->get(self::MOBILE_URL, [
                'bulan' => $parsed->month,
                'tanggal' => $parsed->day,
                'tahun' => $parsed->year,
            ]);
        }
        if (! $response->successful()) {
            throw new RuntimeException("Sumber mengembalikan HTTP {$response->status()} untuk {$date}.");
        }

        return $this->parseDailyPage($date, $response->body());
    }

    /**
     * Mengisi hanya gap yang dapat dibuktikan linear oleh dua tanggal sumber.
     *
     * @param array<string,array<string,mixed>> $rows
     * @param list<string> $failed
     * @return array{array<string,array<string,mixed>>,list<string>}
     */
    private function repairConsecutiveGaps(array $rows, array $failed): array
    {
        sort($failed);
        foreach ($failed as $date) {
            $cursor = Carbon::parse($date);
            $previous = null;
            $next = null;
            for ($distance = 1; $distance <= 4; $distance++) {
                $previous ??= $rows[$cursor->copy()->subDays($distance)->toDateString()] ?? null;
                $next ??= $rows[$cursor->copy()->addDays($distance)->toDateString()] ?? null;
            }
            if ($previous === null || $next === null
                || $previous['sasih_id'] !== $next['sasih_id']
                || $previous['fase'] !== $next['fase']
                || $previous['is_purnama'] || $previous['is_tilem']
                || $next['is_purnama'] || $next['is_tilem']) {
                continue;
            }
            $previousDate = Carbon::parse($previous['tanggal_masehi']);
            $nextDate = Carbon::parse($next['tanggal_masehi']);
            $span = $previousDate->diffInDays($nextDate);
            $numberSpan = $next['nomor_hari'] - $previous['nomor_hari'];
            if ($span !== $numberSpan) {
                continue;
            }
            $offset = $previousDate->diffInDays($cursor);
            $rows[$date] = $previous;
            $rows[$date]['tanggal_masehi'] = $date;
            $rows[$date]['nomor_hari'] = $previous['nomor_hari'] + $offset;
            $rows[$date]['referensi'] = self::BASE_URL.'?bulan='.$cursor->month
                .'&tanggal='.$cursor->day.'&tahun='.$cursor->year;
            $rows[$date]['catatan'] = 'Diturunkan secara linear dari dua tanggal KalenderBali.org yang mengapit; halaman tanggal ini mengembalikan error.';
        }

        ksort($rows);

        return [$rows, array_values(array_filter($failed, fn (string $date): bool => ! isset($rows[$date])))];
    }

    /** @return array<string,mixed> */
    public function parseDailyPage(string $date, string $html): array
    {
        $html = mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
        $lunar = $this->capture($html, 'Purnama,\s*Tilem,\s*Penanggal,\s*dan\s*Pangelong');
        $sasihText = $this->capture($html, 'Sasih');
        // KalenderBali.org menandai sasih nampih dengan awalan "Mala", mis. "Mala Sadha".
        $isNampih = preg_match('/\b(nampih|mala)\b/i', $sasihText) === 1;
        $sasihName = trim((string) preg_replace('/\b(nampih|mala)\b/i', '', $sasihText));
        $sasihName = match (mb_strtolower($sasihName)) {
            'ketiga' => 'Katiga',
            'kelima' => 'Kalima',
            'kenam' => 'Kanem',
            'kepitu' => 'Kapitu',
            'kewulu' => 'Kawolu',
            'kesanga' => 'Kasanga',
            'kedasa' => 'Kadasa',
            'jyestha', 'destha', 'desta' => 'Jiyestha',
            'sada' => 'Sadha',
            default => $sasihName,
        };
        $sasihId = DB::table('master_sasih')
            ->whereRaw('LOWER(nama) = ?', [mb_strtolower($sasihName)])
            ->value('id');
        if ($sasihId === null) {
            throw new RuntimeException("Sasih {$sasihText} tidak dikenal pada {$date}.");
        }

        $normalized = mb_strtolower(trim($lunar));
        if ($normalized === 'purnama') {
            [$fase, $nomor, $purnama, $tilem] = ['penanggal', 15, true, false];
        } elseif ($normalized === 'tilem') {
            [$fase, $nomor, $purnama, $tilem] = ['pangelong', 15, false, true];
        } elseif (preg_match('/^(penanggal|pangelong)\s+(\d{1,2})$/', $normalized, $match)) {
            [$fase, $nomor, $purnama, $tilem] = [$match[1], (int) $match[2], false, false];
        } else {
            throw new RuntimeException("Fase lunar {$lunar} tidak dikenali pada {$date}.");
        }

        return [
            'tanggal_masehi' => $date,
            'sasih_id' => $sasihId,
            'fase' => $fase,
            'nomor_hari' => $nomor,
            'is_purnama' => $purnama,
            'is_tilem' => $tilem,
            'is_nampih_sasih' => $isNampih,
            'sumber' => self::SOURCE,
            'referensi' => self::BASE_URL.'?bulan='.Carbon::parse($date)->month
                .'&tanggal='.Carbon::parse($date)->day.'&tahun='.Carbon::parse($date)->year,
            'catatan' => 'Disinkronkan dari tampilan kalender harian Kalender Bali Digital.',
            'verification_status' => 'verified',
            'verified_by' => null,
            'verified_at' => now(),
            'is_active' => true,
        ];
    }

    private function capture(string $html, string $title): string
    {
        $pattern = '/title\s*=\s*["\']?\s*'.$title.'\s*["\']?\s*>\s*([^<]+)/i';
        if (! preg_match($pattern, $html, $match)) {
            throw new RuntimeException("Elemen {$title} tidak ditemukan.");
        }

        return trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
