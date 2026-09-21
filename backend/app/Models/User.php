<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ── Relationships ────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function automationJobs(): HasMany
    {
        return $this->hasMany(AutomationJob::class);
    }

    // ── Helpers ──────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role && $this->role->isAdmin();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->role) {
            return false;
        }

        // Administrators have all permissions
        if ($this->isAdmin()) {
            return true;
        }

        return $this->role->hasPermission($permission);
    }

    /**
     * Get all permission names for this user's role.
     */
    public function getPermissionNames(): array
    {
        if (!$this->role) {
            return [];
        }

        if ($this->isAdmin()) {
            return Permission::pluck('name')->toArray();
        }

        return $this->role->permissions->pluck('name')->toArray();
    }
}
