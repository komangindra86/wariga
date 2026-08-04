<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function record(string $action, Model|string $entity, ?array $old = null, ?array $new = null, int|string|null $entityId = null): void
    {
        $entityType = $entity instanceof Model ? $entity::class : $entity;
        $entityId ??= $entity instanceof Model ? $entity->getKey() : null;

        AuditLog::query()->create([
            'user_id' => auth()->id(), 'action' => $action,
            'entity_type' => $entityType, 'entity_id' => $entityId,
            'old_values' => $old, 'new_values' => $new,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
