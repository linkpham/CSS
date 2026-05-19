<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for each dashboard module
        $permissions = [
            // Dashboard Overview
            'dashboard.view' => 'View Dashboard Overview',
            
            // Daily Operations
            'daily-ops.view' => 'View Daily Operations',
            
            // Teachers Module
            'teachers.view' => 'View Teachers Statistics',
            'teachers.manage' => 'Manage Teachers Data',
            
            // Revenue Module
            'revenue.view' => 'View Revenue Reports',
            'revenue.export' => 'Export Revenue Data',
            
            // Quality Module
            'quality.view' => 'View Quality Metrics',
            'quality.manage' => 'Manage Quality Settings',
            
            // Admin Users Management
            'admins.view' => 'View Admin Users',
            'admins.create' => 'Create Admin Users',
            'admins.edit' => 'Edit Admin Users',
            'admins.delete' => 'Delete Admin Users',
            'admins.roles' => 'Assign Roles to Admin Users',
            
            // Settings
            'settings.view' => 'View Settings',
            'settings.manage' => 'Manage Settings',
            
            // Reports
            'reports.view' => 'View Reports',
            'reports.export' => 'Export Reports',
            
            // Activity Logs
            'activity-logs.view' => 'View Activity Logs',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'admin'],
                ['name' => $name, 'guard_name' => 'admin']
            );
        }

        // Create roles with descriptions
        $roles = [
            'Super Admin' => [
                'description' => 'Full access to all dashboard features and settings',
                'permissions' => array_keys($permissions), // All permissions
            ],
            'Admin' => [
                'description' => 'Access to most features except system settings and admin management',
                'permissions' => [
                    'dashboard.view',
                    'daily-ops.view',
                    'teachers.view',
                    'teachers.manage',
                    'revenue.view',
                    'revenue.export',
                    'quality.view',
                    'quality.manage',
                    'admins.view',
                    'reports.view',
                    'reports.export',
                    'activity-logs.view',
                ],
            ],
            'Manager' => [
                'description' => 'Access to view and manage operational data',
                'permissions' => [
                    'dashboard.view',
                    'daily-ops.view',
                    'teachers.view',
                    'revenue.view',
                    'quality.view',
                    'reports.view',
                ],
            ],
            'Viewer' => [
                'description' => 'Read-only access to dashboard data',
                'permissions' => [
                    'dashboard.view',
                    'daily-ops.view',
                    'teachers.view',
                    'revenue.view',
                    'quality.view',
                ],
            ],
        ];

        foreach ($roles as $roleName => $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'admin'],
                [
                    'name' => $roleName,
                    'guard_name' => 'admin',
                ]
            );

            // Sync permissions for the role
            $role->syncPermissions($roleData['permissions']);
        }
    }
}
