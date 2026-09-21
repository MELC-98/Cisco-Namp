<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * Device credentials are stored encrypted.
 * The password and enable_secret fields use accessor/mutator encryption.
 * NEVER log or expose these values in API responses.
 */
class DeviceCredential extends Model
{
    protected $fillable = [
        'device_id',
        'username',
        'password',
        'enable_secret',
    ];

    /**
     * These fields should never appear in JSON/array serialization.
     */
    protected $hidden = [
        'password',
        'enable_secret',
    ];

    // ── Relationships ────────────────────────────────────────

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ── Encryption Accessors/Mutators ────────────────────────

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getDecryptedPassword(): ?string
    {
        try {
            return $this->attributes['password']
                ? Crypt::decryptString($this->attributes['password'])
                : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setEnableSecretAttribute($value): void
    {
        $this->attributes['enable_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getDecryptedEnableSecret(): ?string
    {
        try {
            return $this->attributes['enable_secret']
                ? Crypt::decryptString($this->attributes['enable_secret'])
                : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
