<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceFact extends Model
{
    protected $fillable = [
        'device_id',
        'hostname',
        'model',
        'serial_number',
        'os_version',
        'uptime',
        'hardware',
        'image_file',
        'raw_data',
        'discovered_at',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'discovered_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
