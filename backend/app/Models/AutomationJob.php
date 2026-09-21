<?php

namespace App\Models;

use App\Enums\JobStatus;
use App\Enums\JobType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationJob extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'job_type',
        'status',
        'output',
        'error',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'status' => JobStatus::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ── Lifecycle Methods ────────────────────────────────────

    public function markRunning(): void
    {
        $this->update([
            'status' => JobStatus::RUNNING,
            'started_at' => now(),
        ]);
    }

    public function markSuccess(?string $output = null): void
    {
        $this->update([
            'status' => JobStatus::SUCCESS,
            'output' => $output,
            'finished_at' => now(),
        ]);
    }

    public function markFailed(?string $error = null): void
    {
        $this->update([
            'status' => JobStatus::FAILED,
            'error' => $error,
            'finished_at' => now(),
        ]);
    }

    // ── Computed Attributes ──────────────────────────────────

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        $seconds = $this->started_at->diffInSeconds($this->finished_at);

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remaining = $seconds % 60;
        return "{$minutes}m {$remaining}s";
    }
}
