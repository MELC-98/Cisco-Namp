<?php

namespace App\Enums;

enum JobType: string
{
    case TEST_CONNECTION = 'TEST_CONNECTION';
    case DISCOVER_DEVICE = 'DISCOVER_DEVICE';
    case BACKUP_CONFIG = 'BACKUP_CONFIG';
    case RUN_COMMAND = 'RUN_COMMAND';
    case CONFIGURE_INTERFACE = 'CONFIGURE_INTERFACE';
    case REFRESH_INTERFACES = 'REFRESH_INTERFACES';
    case CONFIGURE_DEVICE = 'CONFIGURE_DEVICE';

    public function label(): string
    {
        return match($this) {
            self::TEST_CONNECTION => 'Test Connection',
            self::DISCOVER_DEVICE => 'Discover Device',
            self::BACKUP_CONFIG => 'Backup Configuration',
            self::RUN_COMMAND => 'Run Command',
            self::CONFIGURE_INTERFACE => 'Configure Interface',
            self::REFRESH_INTERFACES => 'Refresh Interfaces',
            self::CONFIGURE_DEVICE => 'Configure Device',
        };
    }
}
