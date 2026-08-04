<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class UserContribution extends Model
{
    protected $fillable = [
        'user_id', 'type', 'status', 'is_anonymous', 'contact_email', 'subject',
        'message', 'activity_name', 'related_date', 'region', 'tradition',
        'source_reference', 'consented_at',
    ];

    protected function casts(): array
    {
        return ['is_anonymous' => 'boolean', 'related_date' => 'date', 'consented_at' => 'datetime'];
    }
}
