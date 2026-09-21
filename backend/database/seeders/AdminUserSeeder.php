<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the default administrator from environment variables.
     * Never hardcode production credentials.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'administrator')->first();

        if (!$adminRole) {
            $this->command->error('Administrator role not found. Run RolePermissionSeeder first.');
            return;
        }

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@cisco-namp.local')],
            [
                'name' => env('ADMIN_NAME', 'System Administrator'),
                'password' => env('ADMIN_PASSWORD', 'change_me'),
                'role_id' => $adminRole->id,
                'status' => 'active',
            ]
        );

        $this->command->info('Admin user created/verified: ' . env('ADMIN_EMAIL', 'admin@cisco-namp.local'));
    }
}
