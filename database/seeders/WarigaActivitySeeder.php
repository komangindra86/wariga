<?php

namespace Database\Seeders;

use App\Models\WarigaActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WarigaActivitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            ['PWI', 'Pawiwahan', 'Manusa Yadnya'],
            ['NGB', 'Ngaben', 'Pitra Yadnya'],
            ['MEP', 'Mepandes', 'Manusa Yadnya'],
            ['MLS', 'Melaspas', 'Dewa Yadnya'],
            ['MBR', 'Membangun rumah', 'Kehidupan'],
            ['PNR', 'Pindah rumah', 'Kehidupan'],
            ['USH', 'Mulai usaha', 'Usaha'],
            ['TNM', 'Menanam', 'Pertanian'],
            ['PTP', 'Potong pohon', 'Pertanian'],
            ['MYD', 'Manusa Yadnya', 'Manusa Yadnya'],
        ];

        foreach ($activities as $index => [$code, $name, $category]) {
            WarigaActivity::query()->updateOrCreate(
                ['kode' => $code],
                [
                    'nama' => $name,
                    'slug' => Str::slug($name),
                    'kategori' => $category,
                    'deskripsi' => "Master kegiatan {$name}.",
                    'disclaimer' => (string) config('wariga.reference_disclosure')
                        .' Konfirmasikan keputusan adat penting kepada ahli Wariga.',
                    'urutan' => $index + 1,
                    'is_active' => true,
                ],
            );
        }

    }
}
