<?php

namespace App\Models;

use App\Enums\DeviceCategory;
use App\Enums\DeviceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    protected $fillable = [
        'hostname',
        'management_ip',
        'ssh_port',
        'vendor',
        'category',
        'device_type',
        'model',
        'serial_number',
        'os_name',
        'os_version',
        'site_id',
        'description',
        'status',
        'last_seen_at',
    ];

    protected $casts = [
        'status' => DeviceStatus::class,
        'category' => DeviceCategory::class,
        'last_seen_at' => 'datetime',
        'ssh_port' => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function credential(): HasOne
    {
        return $this->hasOne(DeviceCredential::class);
    }

    public function facts(): HasMany
    {
        return $this->hasMany(DeviceFact::class);
    }

    public function latestFact(): HasOne
    {
        return $this->hasOne(DeviceFact::class)->latestOfMany();
    }

    public function interfaces(): HasMany
    {
        return $this->hasMany(DeviceInterface::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(ConfigurationBackup::class);
    }

    public function latestBackup(): HasOne
    {
        return $this->hasOne(ConfigurationBackup::class)->latestOfMany();
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(AutomationJob::class);
    }

    public function commandResults(): HasMany
    {
        return $this->hasMany(CommandResult::class);
    }

    public function configurationChanges(): HasMany
    {
        return $this->hasMany(ConfigurationChange::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeOnline($query)
    {
        return $query->where('status', DeviceStatus::ONLINE);
    }

    public function scopeOffline($query)
    {
        return $query->where('status', DeviceStatus::OFFLINE);
    }

    public function scopeRouters($query)
    {
        return $query->where('category', DeviceCategory::ROUTER);
    }

    public function scopeSwitches($query)
    {
        return $query->where('category', DeviceCategory::SWITCH);
    }

    // ── Helpers ──────────────────────────────────────────────

    public function isOnline(): bool
    {
        return $this->status === DeviceStatus::ONLINE;
    }

    public function markOnline(): void
    {
        $this->update([
            'status' => DeviceStatus::ONLINE,
            'last_seen_at' => now(),
        ]);
    }

    public function markOffline(): void
    {
        $this->update([
            'status' => DeviceStatus::OFFLINE,
        ]);
    }
}
