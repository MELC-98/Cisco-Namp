<?php

namespace App\Enums;

enum JobStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::RUNNING => 'Running',
            self::SUCCESS => 'Success',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'gray',
            self::RUNNING => 'blue',
            self::SUCCESS => 'green',
            self::FAILED => 'red',
            self::CANCELLED => 'yellow',
        };
    }
}
