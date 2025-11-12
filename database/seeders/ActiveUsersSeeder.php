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
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'api']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $instructorRole = Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'api']);
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'api']);

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
        if (! $superadmin->hasRole('superadmin')) {
            $superadmin->assignRole('superadmin');
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
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
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
        if (! $instructor->hasRole('instructor')) {
            $instructor->assignRole('instructor');
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
        if (! $student->hasRole('student')) {
            $student->assignRole('student');
        }
    }
}
