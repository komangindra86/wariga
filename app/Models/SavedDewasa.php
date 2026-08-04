<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedDewasa extends Model
{
    protected $table = 'saved_dewasa';

    protected $fillable = [
        'user_id', 'activity_id', 'tanggal', 'score_snapshot', 'status_snapshot',
        'notes', 'result_snapshot', 'engine_version', 'rule_version', 'dataset_version',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'score_snapshot' => 'integer', 'result_snapshot' => 'array'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(WarigaActivity::class);
    }
}
