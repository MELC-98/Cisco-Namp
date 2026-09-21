<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigurationBackup extends Model
{
    protected $fillable = [
        'device_id',
        'user_id',
        'filename',
        'file_path',
        'file_size',
        'checksum',
        'backup_type',
        'status',
        'error_message',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
