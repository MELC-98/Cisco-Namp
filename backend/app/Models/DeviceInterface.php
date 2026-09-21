<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceInterface extends Model
{
    protected $fillable = [
        'device_id',
        'name',
        'description',
        'ip_address',
        'subnet_mask',
        'mac_address',
        'speed',
        'duplex',
        'admin_status',
        'oper_status',
        'type',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function isUp(): bool
    {
        return $this->admin_status === 'up' && $this->oper_status === 'up';
    }
}
