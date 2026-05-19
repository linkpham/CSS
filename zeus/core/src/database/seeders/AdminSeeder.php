<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default Super Admin (admin@localhost with P@ssword2026)
        $superAdmin = Admin::firstOrCreate(
            ['email' => 'admin@localhost'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('P@ssword2026'),
                'role' => Admin::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ]
        );
        $superAdmin->syncRoles(['Super Admin']);

        // Create additional admins for development
        if (app()->environment('local', 'development')) {
            $admin = Admin::firstOrCreate(
                ['email' => 'admin2@zeus.vn'],
                [
                    'name' => 'Admin User',
                    'password' => Hash::make('Zeus@2024'),
                    'role' => Admin::ROLE_ADMIN,
                    'is_active' => true,
                ]
            );
            $admin->syncRoles(['Admin']);

            $manager = Admin::firstOrCreate(
                ['email' => 'manager@zeus.vn'],
                [
                    'name' => 'Manager',
                    'password' => Hash::make('Zeus@2024'),
                    'role' => Admin::ROLE_MANAGER,
                    'is_active' => true,
                ]
            );
            $manager->syncRoles(['Manager']);

            $viewer = Admin::firstOrCreate(
                ['email' => 'viewer@zeus.vn'],
                [
                    'name' => 'Viewer',
                    'password' => Hash::make('Zeus@2024'),
                    'role' => Admin::ROLE_VIEWER,
                    'is_active' => true,
                ]
            );
            $viewer->syncRoles(['Viewer']);
        }
    }
}
