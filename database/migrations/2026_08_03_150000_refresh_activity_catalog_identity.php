<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'Aneka Kegiatan' => 'Aktivitas Sehari-hari',
            'Pembangunan' => 'Rumah & Bangunan',
            'Peralatan-Senjata' => 'Peralatan & Keterampilan',
            'Pertanian-Perkebunan' => 'Tani & Kebun',
            'Peternakan-Perikanan' => 'Ternak & Perairan',
            'Upacara' => 'Upacara & Yadnya',
        ] as $old => $new) {
            DB::table('wariga_activities')->where('kategori', $old)->update(['kategori' => $new]);
        }

        DB::table('wariga_activities')->orderBy('id')->each(function (object $activity): void {
            $name = mb_strtolower((string) $activity->nama);
            $icon = match (true) {
                str_contains($name, 'perjalanan'), str_contains($name, 'bepergian') => '🧭',
                str_contains($name, 'usaha'), str_contains($name, 'dagang') => '💼',
                str_contains($name, 'rapat'), str_contains($name, 'peraturan') => '📝',
                str_contains($name, 'belajar'), str_contains($name, 'sekolah') => '📚',
                str_contains($name, 'obat'), str_contains($name, 'sakit') => '🌿',
                str_contains($name, 'menikah'), str_contains($name, 'pernikahan') => '💍',
                str_contains($name, 'rumah'), str_contains($name, 'bangunan') => '🏠',
                str_contains($name, 'air'), str_contains($name, 'sumur'), str_contains($name, 'irigasi') => '💧',
                str_contains($name, 'ikan'), str_contains($name, 'laut') => '🐟',
                str_contains($name, 'ternak') => '🐄',
                str_contains($name, 'tanam'), str_contains($name, 'kebun') => '🌱',
                $activity->kategori === 'Upacara & Yadnya' => '🙏',
                $activity->kategori === 'Rumah & Bangunan' => '🏗️',
                $activity->kategori === 'Peralatan & Keterampilan' => '🛠️',
                $activity->kategori === 'Tani & Kebun' => '🌾',
                $activity->kategori === 'Ternak & Perairan' => '🐾',
                default => '✨',
            };
            DB::table('wariga_activities')->where('id', $activity->id)->update(['icon' => $icon]);
        });
    }

    public function down(): void {}
};
