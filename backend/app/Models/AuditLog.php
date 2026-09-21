<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit logs are immutable. No update or delete operations
 * should ever be performed on this model from the application.
 */
class AuditLog extends Model
{
    public $timestamps = false; // Only created_at, no updated_at

    protected $fillable = [
        'user_id',
        'action',
        'object_type',
        'object_id',
        'description',
        'source_ip',
        'status',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
