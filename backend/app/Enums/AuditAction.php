<?php

namespace App\Enums;

enum AuditAction: string
{
    // Authentication
    case LOGIN = 'LOGIN';
    case LOGIN_FAILED = 'LOGIN_FAILED';
    case LOGOUT = 'LOGOUT';

    // User management
    case CREATE_USER = 'CREATE_USER';
    case UPDATE_USER = 'UPDATE_USER';
    case DELETE_USER = 'DELETE_USER';
    case DISABLE_USER = 'DISABLE_USER';
    case ENABLE_USER = 'ENABLE_USER';
    case CHANGE_ROLE = 'CHANGE_ROLE';
    case RESET_PASSWORD = 'RESET_PASSWORD';

    // Device management
    case CREATE_DEVICE = 'CREATE_DEVICE';
    case UPDATE_DEVICE = 'UPDATE_DEVICE';
    case DELETE_DEVICE = 'DELETE_DEVICE';

    // Automation operations
    case TEST_CONNECTION = 'TEST_CONNECTION';
    case DISCOVER_DEVICE = 'DISCOVER_DEVICE';
    case RUN_COMMAND = 'RUN_COMMAND';
    case BACKUP_CONFIG = 'BACKUP_CONFIG';
    case DEPLOY_CONFIG = 'DEPLOY_CONFIG';
    case CONFIG_FAILED = 'CONFIG_FAILED';
    case CONFIGURE_DEVICE = 'CONFIGURE_DEVICE';

    public function label(): string
    {
        return str_replace('_', ' ', ucwords(strtolower($this->value), '_'));
    }
}
