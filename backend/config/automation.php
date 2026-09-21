<?php

// ============================================================
// Automation Service Configuration
// ============================================================

return [
    /*
    |--------------------------------------------------------------------------
    | FastAPI Automation Service URL
    |--------------------------------------------------------------------------
    | The internal Docker network URL for the Python FastAPI service.
    | This should never be publicly accessible.
    */
    'api_url' => env('AUTOMATION_API_URL', 'http://automation:8001'),

    /*
    |--------------------------------------------------------------------------
    | API Key for Service-to-Service Authentication
    |--------------------------------------------------------------------------
    | Shared secret between Laravel and FastAPI for request authentication.
    */
    'api_key' => env('AUTOMATION_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout (seconds)
    |--------------------------------------------------------------------------
    | Maximum time to wait for automation service responses.
    | Device operations (discovery, backup) can take time over SSH.
    */
    'timeout' => env('AUTOMATION_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Connection Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'connect_timeout' => env('AUTOMATION_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Backup Storage Path
    |--------------------------------------------------------------------------
    */
    'backup_path' => env('BACKUP_STORAGE_PATH', storage_path('backups')),

    /*
    |--------------------------------------------------------------------------
    | Approved Show Commands
    |--------------------------------------------------------------------------
    | Whitelist of Cisco show commands that users may execute.
    | DO NOT add configuration commands here.
    */
    'approved_commands' => [
        'show version',
        'show running-config',
        'show startup-config',
        'show ip interface brief',
        'show interfaces',
        'show interfaces status',
        'show interfaces trunk',
        'show ip route',
        'show ipv6 route',
        'show arp',
        'show mac address-table',
        'show cdp neighbors',
        'show cdp neighbors detail',
        'show lldp neighbors',
        'show inventory',
        'show vlan brief',
        'show vlan',
        'show ip protocols',
        'show ip ospf neighbor',
        'show ip ospf interface',
        'show ip bgp summary',
        'show ip bgp',
        'show ip eigrp neighbors',
        'show spanning-tree',
        'show spanning-tree summary',
        'show port-security',
        'show port-security interface',
        'show etherchannel summary',
        'show standby',
        'show hsrp',
        'show vrrp',
        'show processes cpu',
        'show memory',
        'show environment',
        'show power inline',
        'show clock',
        'show logging',
        'show ntp status',
        'show ntp associations',
        'show users',
        'show ssh',
        'show ip access-lists',
        'show ip nat translations',
        'show crypto isakmp sa',
        'show crypto ipsec sa',
    ],
];
