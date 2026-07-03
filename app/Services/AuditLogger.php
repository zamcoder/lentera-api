<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * AuditLogger — catat tiap tindakan admin/moderator (§A6): siapa/aksi/target/waktu.
 */
class AuditLogger
{
    public function log(
        ?string $actorId,
        string $action,
        ?string $targetType = null,
        ?string $targetId = null,
        ?array $meta = null,
        ?Request $request = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => $actorId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'meta' => $meta,
            'ip' => $request?->ip(),
        ]);
    }
}
