<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Site;
use Illuminate\Database\Seeder;

class DemoDeviceSeeder extends Seeder
{
    /**
     * Seed demo devices — only in development/local environment.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->warn('Skipping demo device seeder in production.');
            return;
        }

        // Create demo sites
        $dc = Site::firstOrCreate(
            ['code' => 'DC-01'],
            ['name' => 'Main Data Center', 'location' => 'Building A, Floor 2']
        );

        $branch = Site::firstOrCreate(
            ['code' => 'BR-NYC'],
            ['name' => 'New York Branch', 'location' => 'Manhattan Office']
        );

        // Create demo devices
        $devices = [
            [
                'hostname' => 'CORE-RTR-01',
                'management_ip' => '10.99.0.1',
                'category' => 'router',
                'device_type' => 'cisco_xe',
                'model' => 'ISR 4451-X',
                'os_name' => 'IOS XE',
                'os_version' => '17.06.05',
                'site_id' => $dc->id,
                'description' => 'Core router — main data center',
                'status' => 'online',
                'last_seen_at' => now(),
            ],
            [
                'hostname' => 'CORE-RTR-02',
                'management_ip' => '10.99.0.2',
                'category' => 'router',
                'device_type' => 'cisco_xe',
                'model' => 'ISR 4451-X',
                'os_name' => 'IOS XE',
                'os_version' => '17.06.05',
                'site_id' => $dc->id,
                'description' => 'Core router — redundant',
                'status' => 'online',
                'last_seen_at' => now(),
            ],
            [
                'hostname' => 'DIST-SW-01',
                'management_ip' => '10.99.1.1',
                'category' => 'switch',
                'device_type' => 'cisco_ios',
                'model' => 'Catalyst 9300',
                'os_name' => 'IOS XE',
                'os_version' => '17.09.04',
                'site_id' => $dc->id,
                'description' => 'Distribution switch — rack A',
                'status' => 'online',
                'last_seen_at' => now(),
            ],
            [
                'hostname' => 'ACC-SW-01',
                'management_ip' => '10.99.2.1',
                'category' => 'switch',
                'device_type' => 'cisco_ios',
                'model' => 'Catalyst 2960-X',
                'os_name' => 'IOS',
                'os_version' => '15.2(7)E7',
                'site_id' => $dc->id,
                'description' => 'Access switch — server room',
                'status' => 'online',
                'last_seen_at' => now()->subMinutes(15),
            ],
            [
                'hostname' => 'BR-RTR-NYC-01',
                'management_ip' => '10.100.0.1',
                'category' => 'router',
                'device_type' => 'cisco_xe',
                'model' => 'ISR 4331',
                'os_name' => 'IOS XE',
                'os_version' => '17.03.06',
                'site_id' => $branch->id,
                'description' => 'Branch router — NYC office',
                'status' => 'offline',
                'last_seen_at' => now()->subHours(3),
            ],
        ];

        foreach ($devices as $deviceData) {
            $device = Device::firstOrCreate(
                ['management_ip' => $deviceData['management_ip']],
                $deviceData
            );

            // Add demo credentials (encrypted automatically by model)
            $device->credential()->firstOrCreate(
                ['device_id' => $device->id],
                [
                    'username' => 'admin',
                    'password' => 'cisco123',
                    'enable_secret' => 'enable123',
                ]
            );
        }

        $this->command->info('Demo devices seeded: ' . count($devices) . ' devices');
    }
}
