<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedDewasa extends Model
{
    public $timestamps = false;

    protected $table = 'shared_results';

    protected $fillable = [
        'public_token', 'activity_id', 'tanggal', 'result_snapshot',
        'engine_version', 'rule_version', 'dataset_version', 'expires_at', 'view_count', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date', 'result_snapshot' => 'array', 'expires_at' => 'datetime',
            'view_count' => 'integer', 'created_at' => 'datetime',
        ];
    }
}
