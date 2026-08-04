<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarLunarDate extends Model
{
    protected $fillable = [
        'tanggal_masehi', 'sasih_id', 'fase', 'nomor_hari', 'is_purnama',
        'is_tilem', 'is_nampih_sasih', 'sumber', 'referensi', 'catatan',
        'verification_status', 'verified_by', 'verified_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masehi' => 'date:Y-m-d',
            'nomor_hari' => 'integer',
            'is_purnama' => 'boolean',
            'is_tilem' => 'boolean',
            'is_nampih_sasih' => 'boolean',
            'verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function sasih()
    {
        return $this->belongsTo(MasterSasih::class, 'sasih_id');
    }
}
