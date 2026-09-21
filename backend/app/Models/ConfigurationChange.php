<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigurationChange extends Model
{
    protected $fillable = [
        'device_id',
        'user_id',
        'job_id',
        'backup_id',
        'change_type',
        'configuration',
        'rollback_config',
        'status',
        'verification_result',
        'error_message',
        'approved_at',
        'deployed_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'deployed_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(AutomationJob::class, 'job_id');
    }

    public function backup(): BelongsTo
    {
        return $this->belongsTo(ConfigurationBackup::class, 'backup_id');
    }
}
