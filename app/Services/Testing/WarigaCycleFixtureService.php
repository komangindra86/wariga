<?php

namespace App\Services\Testing;

use App\Services\Calendar\BaliCalendarService;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use RuntimeException;

final class WarigaCycleFixtureService
{
    public const HEADERS = [
        'tanggal',
        'cycle_index',
        'eka_wara',
        'dwi_wara',
        'tri_wara',
        'catur_wara',
        'panca_wara',
        'sad_wara',
        'sapta_wara',
        'asta_wara',
        'sanga_wara',
        'dasa_wara',
        'wuku',
        'urip_sapta',
        'urip_panca',
        'jumlah_urip',
        'formula_status',
    ];

    public function __construct(private readonly BaliCalendarService $calendar) {}

    /**
     * @return list<array<string, int|string>>
     */
    public function generate(string $startDate): array
    {
        $date = CarbonImmutable::createFromFormat(
            '!Y-m-d',
            $startDate,
            (string) config('wariga.timezone', 'Asia/Makassar'),
        );

        if ($date === false || $date->format('Y-m-d') !== $startDate) {
            throw new InvalidArgumentException('Tanggal awal harus menggunakan format YYYY-MM-DD.');
        }

        $rows = [];

        for ($offset = 0; $offset < 210; $offset++) {
            $result = $this->calendar->calculate($date->addDays($offset)->format('Y-m-d'));
            $sapta = $result->wewaran['sapta_wara'];
            $panca = $result->wewaran['panca_wara'];

            $rows[] = [
                'tanggal' => $result->tanggalMasehi,
                'cycle_index' => $result->cycleIndex,
                'eka_wara' => $result->wewaran['eka_wara']?->nama ?? '',
                'dwi_wara' => $result->wewaran['dwi_wara']?->nama ?? '',
                'tri_wara' => $result->wewaran['tri_wara']?->nama ?? '',
                'catur_wara' => $result->wewaran['catur_wara']?->nama ?? '',
                'panca_wara' => $panca?->nama ?? '',
                'sad_wara' => $result->wewaran['sad_wara']?->nama ?? '',
                'sapta_wara' => $sapta?->nama ?? '',
                'asta_wara' => $result->wewaran['asta_wara']?->nama ?? '',
                'sanga_wara' => $result->wewaran['sanga_wara']?->nama ?? '',
                'dasa_wara' => $result->wewaran['dasa_wara']?->nama ?? '',
                'wuku' => $result->wuku->nama,
                'urip_sapta' => $sapta?->urip ?? 0,
                'urip_panca' => $panca?->urip ?? 0,
                'jumlah_urip' => ($sapta?->urip ?? 0) + ($panca?->urip ?? 0),
                'formula_status' => 'belum_divalidasi_ahli',
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, int|string>>  $rows
     */
    public function write(string $path, array $rows): void
    {
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Direktori tidak dapat dibuat: {$directory}");
        }

        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new RuntimeException("File tidak dapat ditulis: {$path}");
        }

        try {
            fputcsv($handle, self::HEADERS);
            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn (string $header): mixed => $row[$header] ?? '', self::HEADERS));
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return list<array<string, string>>
     */
    public function read(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException("File referensi tidak dapat dibaca: {$path}");
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false || array_diff(self::HEADERS, $headers) !== []) {
                throw new InvalidArgumentException('Header CSV referensi ahli tidak lengkap.');
            }

            $rows = [];
            while (($values = fgetcsv($handle)) !== false) {
                if ($values === [null] || $values === []) {
                    continue;
                }
                $values = array_pad($values, count($headers), '');
                $rows[] = array_combine($headers, array_slice($values, 0, count($headers)));
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<array<string, int|string>>  $systemRows
     * @param  list<array<string, string>>  $expertRows
     * @return array{matched: int, different: int, missing: int, results: list<array<string, mixed>>}
     */
    public function compare(array $systemRows, array $expertRows): array
    {
        $expertByDate = [];
        foreach ($expertRows as $row) {
            $expertByDate[$row['tanggal']] = $row;
        }

        $matched = $different = $missing = 0;
        $results = [];
        $comparedHeaders = array_values(array_diff(self::HEADERS, ['tanggal', 'formula_status']));

        foreach ($systemRows as $system) {
            $expert = $expertByDate[$system['tanggal']] ?? null;
            $differences = [];

            if ($expert === null) {
                $status = 'referensi_tidak_tersedia';
                $missing++;
            } else {
                foreach ($comparedHeaders as $header) {
                    if ((string) $system[$header] !== (string) ($expert[$header] ?? '')) {
                        $differences[] = $header;
                    }
                }
                $status = $differences === [] ? 'cocok' : 'berbeda';
                $differences === [] ? $matched++ : $different++;
            }

            $results[] = [
                'tanggal' => $system['tanggal'],
                'hasil_sistem' => $system,
                'data_referensi_ahli' => $expert,
                'status' => $status,
                'catatan_validator' => $differences === []
                    ? ($expert === null ? 'Belum ada data referensi ahli.' : '')
                    : 'Kolom berbeda: '.implode(', ', $differences),
            ];
        }

        return compact('matched', 'different', 'missing', 'results');
    }
}
