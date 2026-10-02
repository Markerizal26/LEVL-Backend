<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('   🚀 LEVL Production Seeder - Master Data Only   ');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('');

        $this->command->info('This seeder will populate:');
        $this->command->info('  ✓ Roles & Permissions (4 roles, 50+ permissions)');
        $this->command->info('  ✓ System Settings (100+ configuration entries)');
        $this->command->info('  ✓ Master Data (categories, difficulty levels, content types)');
        $this->command->info('  ✓ Tags (140+ skill tags)');
        $this->command->info('  ✓ Gamification Levels (100 XP tier levels)');
        $this->command->info('  ✓ Superadmin User (1 production account)');
        $this->command->info('');
        $this->command->info('No demo users, UAT personas, or test data will be created.');
        $this->command->info('');

        // Phase 1: Create roles and permissions (MUST BE FIRST)
        $this->command->info('📋 Phase 1: Setting up Roles & Permissions...');
        $this->call(\Modules\Auth\Database\Seeders\RolePermissionSeeder::class);

        // Phase 2: System settings and reference data
        $this->command->info('');
        $this->command->info('📋 Phase 2: Seeding Master Data & Configuration...');
        $this->call(\Modules\Common\Database\Seeders\SystemSettingSeeder::class);

        // Phase 3: Content and skill classifications
        $this->command->info('');
        $this->command->info('📋 Phase 3: Seeding Content Classifications...');
        $this->call(MasterDataSeeder::class);
        $this->call(\Modules\Common\Database\Seeders\CategorySeeder::class);
        $this->call(\Modules\Schemes\Database\Seeders\TagSeeder::class);

        // Phase 4: Gamification configuration
        $this->command->info('');
        $this->command->info('📋 Phase 4: Seeding Gamification Levels...');
        $this->call(\Modules\Gamification\Database\Seeders\LevelConfigSeeder::class);
        $this->call(\Modules\Gamification\Database\Seeders\XpSourceSeeder::class);

        // Phase 5: Create production superadmin user (MUST BE LAST)
        $this->command->info('');
        $this->command->info('📋 Phase 5: Creating Superadmin Account...');
        $this->call(ProductionSuperAdminSeeder::class);

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('✅ Production seeding completed successfully!');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('📝 Summary:');
        $this->command->info('  • Roles:          4 (Superadmin, Admin, Instructor, Student)');
        $this->command->info('  • Permissions:    50+');
        $this->command->info('  • System Settings: 100+');
        $this->command->info('  • Categories:     80+');
        $this->command->info('  • Difficulty Levels: 3');
        $this->command->info('  • Content Types:  10+');
        $this->command->info('  • Tags:           140+');
        $this->command->info('  • XP Levels:      100');
        $this->command->info('  • Users:          1 (superadmin)');
        $this->command->info('');
        $this->command->info('🔗 Next Steps:');
        $this->command->info('  1. Verify the production API is healthy');
        $this->command->info('  2. Log in with the SUPERADMIN_EMAIL credentials');
        $this->command->info('  3. Keep SUPERADMIN_PASSWORD private');
        $this->command->info('');
    }
}
