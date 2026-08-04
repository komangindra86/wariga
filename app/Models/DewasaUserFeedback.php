<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DewasaUserFeedback extends Model
{
    protected $table = 'dewasa_user_feedback';

    protected $fillable = [
        'user_id', 'activity_id', 'recommendation_date', 'rating',
        'reason', 'region', 'tradition', 'consulted_expert',
        'expert_role', 'predicted_status', 'predicted_score',
        'learning_consent', 'submission_count', 'is_suspicious', 'consented_at',
    ];

    protected function casts(): array
    {
        return [
            'recommendation_date' => 'date',
            'consulted_expert' => 'boolean',
            'learning_consent' => 'boolean',
            'is_suspicious' => 'boolean',
            'consented_at' => 'datetime',
            'predicted_score' => 'integer',
            'submission_count' => 'integer',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(WarigaActivity::class);
    }
}
