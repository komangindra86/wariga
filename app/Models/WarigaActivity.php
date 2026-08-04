<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarigaActivity extends Model
{
    protected $fillable = [
        'kode', 'nama', 'slug', 'kategori', 'deskripsi', 'disclaimer',
        'icon', 'urutan', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'is_active' => 'boolean',
        ];
    }

}
