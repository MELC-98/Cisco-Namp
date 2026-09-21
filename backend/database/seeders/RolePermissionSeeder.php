<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ── Create Permissions ──────────────────────────────────
        $permissions = [
            // Dashboard
            ['name' => 'view_dashboard', 'display_name' => 'View Dashboard', 'group' => 'dashboard'],

            // Devices
            ['name' => 'view_devices', 'display_name' => 'View Devices', 'group' => 'devices'],
            ['name' => 'create_devices', 'display_name' => 'Add Devices', 'group' => 'devices'],
            ['name' => 'edit_devices', 'display_name' => 'Edit Devices', 'group' => 'devices'],
            ['name' => 'delete_devices', 'display_name' => 'Delete Devices', 'group' => 'devices'],
            ['name' => 'test_devices', 'display_name' => 'Test Device Connection', 'group' => 'devices'],

            // Commands
            ['name' => 'execute_commands', 'display_name' => 'Execute Approved Commands', 'group' => 'commands'],

            // Configuration
            ['name' => 'configure_devices', 'display_name' => 'Configure Devices', 'group' => 'configuration'],

            // Backups
            ['name' => 'view_backups', 'display_name' => 'View Backups', 'group' => 'backups'],
            ['name' => 'create_backups', 'display_name' => 'Create Backups', 'group' => 'backups'],

            // Jobs
            ['name' => 'view_jobs', 'display_name' => 'View Jobs', 'group' => 'jobs'],

            // Users
            ['name' => 'manage_users', 'display_name' => 'Manage Users', 'group' => 'admin'],
            ['name' => 'create_users', 'display_name' => 'Create Users', 'group' => 'admin'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users', 'group' => 'admin'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users', 'group' => 'admin'],
            ['name' => 'reset_passwords', 'display_name' => 'Reset Passwords', 'group' => 'admin'],

            // Audit
            ['name' => 'view_audit_logs', 'display_name' => 'View Audit Logs', 'group' => 'admin'],

            // Settings
            ['name' => 'manage_settings', 'display_name' => 'Manage Settings', 'group' => 'admin'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }

        // ── Create Roles ────────────────────────────────────────

        $adminRole = Role::firstOrCreate(
            ['name' => 'administrator'],
            [
                'display_name' => 'Administrator',
                'description' => 'Full system access. Can manage all users, devices, and settings.',
                'is_system' => true,
            ]
        );

        $engineerRole = Role::firstOrCreate(
            ['name' => 'network_engineer'],
            [
                'display_name' => 'Network Engineer',
                'description' => 'Can manage devices, run commands, create backups, and configure interfaces.',
                'is_system' => true,
            ]
        );

        $viewerRole = Role::firstOrCreate(
            ['name' => 'viewer'],
            [
                'display_name' => 'Viewer',
                'description' => 'Read-only access to dashboard, devices, and backups.',
                'is_system' => true,
            ]
        );
        $technicianRole = Role::firstOrCreate(
            ['name' => 'technicien_reseau'],
            [
                'display_name' => 'Technicien Réseau',
                'description' => 'Peut voir les équipements, la topologie, exécuter des commandes de base et voir les sauvegardes.',
                'is_system' => true,
            ]
        );

        // ── Assign Permissions to Roles ─────────────────────────

        // Administrator gets ALL permissions
        $allPermIds = Permission::pluck('id');
        $adminRole->permissions()->sync($allPermIds);

        // Network Engineer permissions
        $engineerPerms = Permission::whereIn('name', [
            'view_dashboard',
            'view_devices',
            'create_devices',
            'edit_devices',
            'test_devices',
            'execute_commands',
            'configure_devices',
            'view_backups',
            'create_backups',
            'view_jobs',
        ])->pluck('id');
        $engineerRole->permissions()->sync($engineerPerms);

        // Technicien Réseau permissions
        $technicianPerms = Permission::whereIn('name', [
            'view_dashboard',
            'view_devices',
            'test_devices',
            'execute_commands',
            'view_backups',
            'view_jobs',
        ])->pluck('id');
        $technicianRole->permissions()->sync($technicianPerms);

        // Viewer permissions
        $viewerPerms = Permission::whereIn('name', [
            'view_dashboard',
            'view_devices',
            'view_backups',
            'view_jobs',
        ])->pluck('id');
        $viewerRole->permissions()->sync($viewerPerms);
    }
}
