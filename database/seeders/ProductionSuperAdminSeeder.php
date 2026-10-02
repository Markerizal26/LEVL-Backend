<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role;

class ProductionSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('👤 Creating superadmin user...');

        $email = env('SUPERADMIN_EMAIL');
        $password = env('SUPERADMIN_PASSWORD');

        if (! is_string($email) || trim($email) === '' || ! is_string($password) || strlen($password) < 12) {
            throw new \RuntimeException('SUPERADMIN_EMAIL and SUPERADMIN_PASSWORD (minimum 12 characters) are required.');
        }

        $username = (string) env('SUPERADMIN_USERNAME', 'superadmin');
        $name = (string) env('SUPERADMIN_NAME', 'Superadmin');

        // Ensure Superadmin role exists
        $superadminRole = Role::where('name', 'Superadmin')->where('guard_name', 'api')->first();
        if (! $superadminRole) {
            $this->command->error('❌ Superadmin role not found. Run RolePermissionSeeder first.');
            return;
        }

        // Create superadmin user
        $superadmin = User::firstOrCreate(
            ['email' => $email],
            [
                'username' => $username,
                'name' => $name,
                'password' => Hash::make($password),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Assign Superadmin role with all permissions
        $superadmin->syncRoles('Superadmin');

        $this->command->info('');
        $this->command->info('✅ Superadmin user created successfully!');
        $this->command->info('');
        $this->command->info('🔐 Login Credentials:');
        $this->command->info('   Email:    '.$email);
        $this->command->info('   Username: '.$username);
        $this->command->info('   Password: value from SUPERADMIN_PASSWORD');
        $this->command->info('');
        $this->command->warn('⚠️  Keep the superadmin password private and rotate it periodically.');
        $this->command->info('');
    }
}
