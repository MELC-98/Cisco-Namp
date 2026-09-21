<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    /**
     * Record an audit log entry.
     * Audit logs are append-only and immutable from the application.
     */
    public function log(
        AuditAction $action,
        ?int $userId = null,
        ?string $objectType = null,
        ?int $objectId = null,
        ?string $description = null,
        string $status = 'success',
        ?array $metadata = null,
        ?Request $request = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action->value,
            'object_type' => $objectType,
            'object_id' => $objectId,
            'description' => $description,
            'source_ip' => $request?->ip(),
            'status' => $status,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * Convenience method to log from the current authenticated user and request.
     */
    public function logAction(
        AuditAction $action,
        ?string $objectType = null,
        ?int $objectId = null,
        ?string $description = null,
        string $status = 'success',
        ?array $metadata = null
    ): AuditLog {
        $request = request();
        $user = $request->user();

        return $this->log(
            action: $action,
            userId: $user?->id,
            objectType: $objectType,
            objectId: $objectId,
            description: $description,
            status: $status,
            metadata: $metadata,
            request: $request
        );
    }
}
