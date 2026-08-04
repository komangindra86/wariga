<?php

namespace App\Services\Lunar;

use App\Models\CalendarLunarDate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class LunarCsvImporter
{
    public const HEADERS = [
        'tanggal_masehi', 'sasih_kode', 'fase', 'nomor_hari', 'is_purnama',
        'is_tilem', 'is_nampih_sasih', 'sumber', 'referensi', 'catatan',
        'verification_status',
    ];

    /**
     * @return array{valid: list<array<string, mixed>>, failed: list<array<string, mixed>>, duplicates: list<array<string, mixed>>}
     */
    public function preview(string $path): array
    {
        if (! is_readable($path)) {
            throw new InvalidArgumentException("File CSV tidak dapat dibaca: {$path}");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('File CSV gagal dibuka.');
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false) {
                throw new InvalidArgumentException('File CSV kosong.');
            }

            $headers = array_map(
                static fn (string $value): string => trim($value, " \t\n\r\0\x0B\xEF\xBB\xBF"),
                $headers,
            );

            if ($headers !== self::HEADERS) {
                throw new InvalidArgumentException(
                    'Header CSV tidak valid. Urutan wajib: '.implode(',', self::HEADERS)
                );
            }

            $valid = [];
            $failed = [];
            $duplicates = [];
            $seenDates = [];
            $line = 1;

            while (($values = fgetcsv($handle)) !== false) {
                $line++;

                if ($values === [null] || $values === []) {
                    continue;
                }

                if (count($values) !== count($headers)) {
                    $failed[] = ['baris' => $line, 'errors' => ['Jumlah kolom tidak sesuai header.']];

                    continue;
                }

                $row = array_combine($headers, $values);
                [$normalized, $errors] = $this->validateRow($row);

                if ($errors !== []) {
                    $failed[] = ['baris' => $line, 'tanggal_masehi' => $row['tanggal_masehi'], 'errors' => $errors];

                    continue;
                }

                $date = $normalized['tanggal_masehi'];

                if (isset($seenDates[$date])) {
                    $duplicates[] = ['baris' => $line, 'tanggal_masehi' => $date, 'jenis' => 'dalam_file'];

                    continue;
                }

                $seenDates[$date] = true;

                if (CalendarLunarDate::query()->whereDate('tanggal_masehi', $date)->exists()) {
                    $duplicates[] = ['baris' => $line, 'tanggal_masehi' => $date, 'jenis' => 'database'];

                    continue;
                }

                $normalized['_baris'] = $line;
                $valid[] = $normalized;
            }

            return compact('valid', 'failed', 'duplicates');
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array{imported: int, failed: list<array<string, mixed>>, duplicates: list<array<string, mixed>>}
     */
    public function import(string $path): array
    {
        $preview = $this->preview($path);

        $imported = DB::transaction(function () use ($preview): int {
            foreach ($preview['valid'] as $row) {
                unset($row['_baris']);
                CalendarLunarDate::query()->create($row);
            }

            return count($preview['valid']);
        });

        return [
            'imported' => $imported,
            'failed' => $preview['failed'],
            'duplicates' => $preview['duplicates'],
        ];
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array{array<string, mixed>, list<string>}
     */
    private function validateRow(array $row): array
    {
        $errors = [];
        $date = trim((string) $row['tanggal_masehi']);
        try {
            $parsedDate = Carbon::createFromFormat('!Y-m-d', $date);
            $dateErrors = Carbon::getLastErrors();
        } catch (Throwable) {
            $parsedDate = false;
            $dateErrors = false;
        }

        if ($parsedDate === false
            || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        ) {
            $errors[] = 'tanggal_masehi harus berupa tanggal valid YYYY-MM-DD.';
        }

        $sasih = DB::table('master_sasih')
            ->where('kode', trim((string) $row['sasih_kode']))
            ->where('is_active', true)
            ->first();

        if ($sasih === null) {
            $errors[] = 'sasih_kode tidak ditemukan atau tidak aktif.';
        }

        $fase = trim((string) $row['fase']);
        if (! in_array($fase, ['penanggal', 'pangelong'], true)) {
            $errors[] = 'fase harus penanggal atau pangelong.';
        }

        $nomorHari = filter_var($row['nomor_hari'], FILTER_VALIDATE_INT);
        if ($nomorHari === false || $nomorHari < 1 || $nomorHari > 15) {
            $errors[] = 'nomor_hari harus bilangan 1 sampai 15.';
        }

        $booleans = [];
        foreach (['is_purnama', 'is_tilem', 'is_nampih_sasih'] as $field) {
            $booleans[$field] = $this->parseBoolean($row[$field]);
            if ($booleans[$field] === null) {
                $errors[] = "{$field} harus bernilai 0/1, true/false, atau ya/tidak.";
            }
        }

        if ($booleans['is_purnama'] === true && ! ($fase === 'penanggal' && $nomorHari === 15)) {
            $errors[] = 'Purnama hanya valid pada penanggal hari ke-15.';
        }

        if ($booleans['is_tilem'] === true && ! ($fase === 'pangelong' && $nomorHari === 15)) {
            $errors[] = 'Tilem hanya valid pada pangelong hari ke-15.';
        }

        foreach (['sumber', 'referensi'] as $field) {
            if (trim((string) $row[$field]) === '') {
                $errors[] = "{$field} wajib diisi.";
            }
        }

        $status = trim((string) $row['verification_status']);
        if (! in_array($status, ['draft', 'review', 'verified', 'disputed', 'archived'], true)) {
            $errors[] = 'verification_status tidak valid.';
        }

        if ($errors !== []) {
            return [[], $errors];
        }

        return [[
            'tanggal_masehi' => $date,
            'sasih_id' => $sasih->id,
            'fase' => $fase,
            'nomor_hari' => $nomorHari,
            'is_purnama' => $booleans['is_purnama'],
            'is_tilem' => $booleans['is_tilem'],
            'is_nampih_sasih' => $booleans['is_nampih_sasih'],
            'sumber' => trim((string) $row['sumber']),
            'referensi' => trim((string) $row['referensi']),
            'catatan' => trim((string) $row['catatan']) ?: null,
            'verification_status' => $status,
            'is_active' => true,
        ], []];
    }

    private function parseBoolean(?string $value): ?bool
    {
        return match (strtolower(trim((string) $value))) {
            '1', 'true', 'ya', 'yes' => true,
            '0', 'false', 'tidak', 'no' => false,
            default => null,
        };
    }
}
