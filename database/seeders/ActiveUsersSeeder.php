<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role;

class ActiveUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        $superadminRole = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'api']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'api']);
        $instructorRole = Role::firstOrCreate(['name' => 'Instructor', 'guard_name' => 'api']);
        $studentRole = Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'api']);

        // Create Superadmin user
        $superadmin = User::query()->firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        if (! $superadmin->hasRole('Superadmin')) {
            $superadmin->assignRole('Superadmin');
        }

        // Create Admin user
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        if (! $admin->hasRole('Admin')) {
            $admin->assignRole('Admin');
        }

        // Create Instructor user
        $instructor = User::query()->firstOrCreate(
            ['email' => 'instructor@example.com'],
            [
                'name' => 'Instructor',
                'username' => 'instructor',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        if (! $instructor->hasRole('Instructor')) {
            $instructor->assignRole('Instructor');
        }

        // Create Student user
        $student = User::query()->firstOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Student',
                'username' => 'student',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        if (! $student->hasRole('Student')) {
            $student->assignRole('Student');
        }
    }
}
