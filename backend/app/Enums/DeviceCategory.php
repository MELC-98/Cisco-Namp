<?php

namespace App\Enums;

enum DeviceCategory: string
{
    case ROUTER = 'router';
    case SWITCH = 'switch';
    case FIREWALL = 'firewall';
    case WIRELESS = 'wireless';

    public function label(): string
    {
        return match($this) {
            self::ROUTER => 'Routeur',
            self::SWITCH => 'Commutateur',
            self::FIREWALL => 'Pare-feu',
            self::WIRELESS => 'Sans fil',
        };
    }
}
