<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BaliCalendarMasterSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMaster('master_eka_wara', [
            ['kode' => 'luang', 'nama' => 'Luang', 'urutan' => 1, 'urip' => 1],
        ]);

        $this->seedMaster('master_dwi_wara', [
            ['kode' => 'menga', 'nama' => 'Menga', 'urutan' => 1, 'urip' => 5],
            ['kode' => 'pepet', 'nama' => 'Pepet', 'urutan' => 2, 'urip' => 4],
        ]);

        $this->seedMaster('master_tri_wara', $this->orderedNames([
            'Pasah', 'Beteng', 'Kajeng',
        ]));

        $this->seedMaster('master_catur_wara', $this->orderedNames([
            'Sri', 'Laba', 'Jaya', 'Menala',
        ]));

        $this->seedMaster('master_sapta_wara', [
            ['kode' => 'redite', 'nama' => 'Redite', 'nama_indonesia' => 'Minggu', 'urutan' => 1, 'urip' => 5],
            ['kode' => 'soma', 'nama' => 'Soma', 'nama_indonesia' => 'Senin', 'urutan' => 2, 'urip' => 4],
            ['kode' => 'anggara', 'nama' => 'Anggara', 'nama_indonesia' => 'Selasa', 'urutan' => 3, 'urip' => 3],
            ['kode' => 'buda', 'nama' => 'Buda', 'nama_indonesia' => 'Rabu', 'urutan' => 4, 'urip' => 7],
            ['kode' => 'wraspati', 'nama' => 'Wraspati', 'nama_indonesia' => 'Kamis', 'urutan' => 5, 'urip' => 8],
            ['kode' => 'sukra', 'nama' => 'Sukra', 'nama_indonesia' => 'Jumat', 'urutan' => 6, 'urip' => 6],
            ['kode' => 'saniscara', 'nama' => 'Saniscara', 'nama_indonesia' => 'Sabtu', 'urutan' => 7, 'urip' => 9],
        ]);

        $this->seedMaster('master_panca_wara', [
            ['kode' => 'paing', 'nama' => 'Paing', 'urutan' => 1, 'urip' => 9],
            ['kode' => 'pon', 'nama' => 'Pon', 'urutan' => 2, 'urip' => 7],
            ['kode' => 'wage', 'nama' => 'Wage', 'urutan' => 3, 'urip' => 4],
            ['kode' => 'kliwon', 'nama' => 'Kliwon', 'urutan' => 4, 'urip' => 8],
            ['kode' => 'umanis', 'nama' => 'Umanis', 'urutan' => 5, 'urip' => 5],
        ]);

        $this->seedMaster('master_sad_wara', $this->orderedNames([
            'Tungleh', 'Aryang', 'Urukung', 'Paniron', 'Was', 'Maulu',
        ]));

        $this->seedMaster('master_asta_wara', $this->orderedNames([
            'Sri', 'Indra', 'Guru', 'Yama', 'Ludra', 'Brahma', 'Kala', 'Uma',
        ]));

        $this->seedMaster('master_sanga_wara', $this->orderedNames([
            'Dangu', 'Jangur', 'Gigis', 'Nohan', 'Ogan', 'Erangan', 'Urungan',
            'Tulus', 'Dadi',
        ]));

        $this->seedMaster('master_dasa_wara', [
            ['kode' => 'pandita', 'nama' => 'Pandita', 'urutan' => 1, 'urip' => 1],
            ['kode' => 'pati', 'nama' => 'Pati', 'urutan' => 2, 'urip' => 2],
            ['kode' => 'suka', 'nama' => 'Suka', 'urutan' => 3, 'urip' => 3],
            ['kode' => 'duka', 'nama' => 'Duka', 'urutan' => 4, 'urip' => 4],
            ['kode' => 'sri', 'nama' => 'Sri', 'urutan' => 5, 'urip' => 5],
            ['kode' => 'manuh', 'nama' => 'Manuh', 'urutan' => 6, 'urip' => 6],
            ['kode' => 'manusa', 'nama' => 'Manusa', 'urutan' => 7, 'urip' => 7],
            ['kode' => 'raja', 'nama' => 'Raja', 'urutan' => 8, 'urip' => 8],
            ['kode' => 'dewa', 'nama' => 'Dewa', 'urutan' => 9, 'urip' => 9],
            ['kode' => 'raksasa', 'nama' => 'Raksasa', 'urutan' => 10, 'urip' => 10],
        ]);

        $this->seedMaster('master_wuku', $this->wuku());

        $this->seedMaster('master_sasih', $this->orderedNames([
            'Kasa', 'Karo', 'Katiga', 'Kapat', 'Kalima', 'Kanem',
            'Kapitu', 'Kawolu', 'Kasanga', 'Kadasa', 'Jiyestha', 'Sadha',
        ]));
    }

    /**
     * @param  list<array<string, int|string|null>>  $rows
     */
    private function seedMaster(string $table, array $rows): void
    {
        $now = now();

        foreach ($rows as $row) {
            DB::table($table)->updateOrInsert(
                ['kode' => $row['kode']],
                array_merge([
                    'nama_indonesia' => null,
                    'nama_alternatif' => null,
                    'urip' => null,
                    'deskripsi' => null,
                    'metadata' => null,
                    'verification_status' => 'draft',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $row),
            );
        }
    }

    /**
     * @return list<array{kode: string, nama: string, urutan: int}>
     */
    private function wuku(): array
    {
        $names = [
            'Sinta', 'Landep', 'Ukir', 'Kulantir', 'Tolu', 'Gumbreg',
            'Wariga', 'Warigadian', 'Julungwangi', 'Sungsang', 'Dungulan',
            'Kuningan', 'Langkir', 'Medangsia', 'Pujut', 'Pahang', 'Krulut',
            'Merakih', 'Tambir', 'Medangkungan', 'Matal', 'Uye', 'Menail',
            'Prangbakat', 'Bala', 'Ugu', 'Wayang', 'Kelawu', 'Dukut',
            'Watugunung',
        ];

        return array_map(
            fn (string $name, int $index): array => [
                'kode' => strtolower($name),
                'nama' => $name,
                'urutan' => $index + 1,
            ],
            $names,
            array_keys($names),
        );
    }

    /**
     * @param  list<string>  $names
     * @return list<array{kode: string, nama: string, urutan: int}>
     */
    private function orderedNames(array $names): array
    {
        return array_map(
            fn (string $name, int $index): array => [
                'kode' => strtolower($name),
                'nama' => $name,
                'urutan' => $index + 1,
            ],
            $names,
            array_keys($names),
        );
    }
}
