<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AlaAyuMaster extends Model
{
    protected $fillable = [
        'nama', 'deskripsi', 'komposisi_teks', 'komposisi',
        'sumber_nama', 'sumber_url', 'diimpor_pada',
    ];

    protected function casts(): array
    {
        return [
            'komposisi' => 'array',
            'diimpor_pada' => 'datetime',
        ];
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(
            WarigaActivity::class,
            'ala_ayu_activity_rules',
            'ala_ayu_master_id',
            'activity_id',
        )->withPivot(['effect', 'alahing_priority', 'source_url'])->withTimestamps();
    }
}
