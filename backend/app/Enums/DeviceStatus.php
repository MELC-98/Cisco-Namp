<?php

namespace App\Enums;

enum DeviceStatus: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case WARNING = 'warning';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match($this) {
            self::ONLINE => 'Online',
            self::OFFLINE => 'Offline',
            self::WARNING => 'Warning',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ONLINE => 'green',
            self::OFFLINE => 'red',
            self::WARNING => 'yellow',
            self::UNKNOWN => 'gray',
        };
    }
}
