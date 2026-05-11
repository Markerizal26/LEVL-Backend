<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use App\Support\RealisticSeederContent;
use Illuminate\Database\Seeder;

class AuthComprehensiveDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->newLine();
        $this->command->info('╔════════════════════════════════════════════════════════════╗');
        $this->command->info('║     Auth Module Comprehensive Data Seeding                ║');
        $this->command->info('║     Creating 1000+ users with deterministic demo data      ║');
        $this->command->info('╚════════════════════════════════════════════════════════════╝');
        $this->command->newLine();

        $startTime = microtime(true);

        $this->call([
            RolePermissionSeeder::class,
            UserSeederEnhanced::class,
            ProfileSeeder::class,
            JwtRefreshTokenSeeder::class,
            OtpCodeSeeder::class,
            PasswordResetTokenSeeder::class,
            ProfileAuditLogSeeder::class,
        ]);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->command->newLine();
        $this->command->info('╔════════════════════════════════════════════════════════════╗');
        $this->command->info('║  Seeding completed                                        ║');
        $this->command->info("║  Time taken: {$duration} seconds                          ║");
        $this->command->info('╚════════════════════════════════════════════════════════════╝');
        $this->command->newLine();
        $this->printSummary();
        $this->command->newLine();
    }

    private function printSummary(): void
    {
        $d = RealisticSeederContent::EMAIL_DOMAIN_DEMO;

        $this->command->info('Data summary:');
        $this->command->info('  • Total users: '.\Modules\Auth\Models\User::count());
        $this->command->info('  • Demo accounts: 4');
        $this->command->info('  • Special test users: 8');
        $this->command->info('  • OTP codes: '.\Modules\Auth\Models\OtpCode::count());
        $this->command->info('  • Password reset tokens: '.\Illuminate\Support\Facades\DB::table('password_reset_tokens')->count());

        $activityCount = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasTable('user_activities')
            ? \Illuminate\Support\Facades\DB::table('user_activities')->count()
            : 0;
        $this->command->info('  • User activities: '.$activityCount);
        $this->command->newLine();

        $this->command->info('Demo credentials (password: password):');
        $this->command->info('  Email                        | Username         | Role       | Status');
        $this->command->info('  ─────────────────────────────┼──────────────────┼────────────┼────────');
        $this->command->info("  superadmin.demo@{$d}     | superadmin_demo  | Superadmin | Active");
        $this->command->info("  admin.demo@{$d}          | admin_demo       | Admin      | Active");
        $this->command->info("  instructor.demo@{$d}     | instructor_demo  | Instructor | Active");
        $this->command->info("  student.demo@{$d}        | student_demo     | Student    | Active");
        $this->command->newLine();

        $this->command->info('Special test users (password: password unless noted):');
        $this->command->info("  • student.unverified@{$d} — unverified email");
        $this->command->info("  • student.no-password@{$d} — no password set");
        $this->command->info("  • student.inactive@{$d} — inactive");
        $this->command->info("  • student.banned@{$d} — banned");
        $this->command->info("  • student.email-change@{$d} — pending email change");
        $this->command->info("  • student.deletion-pending@{$d} — pending deletion");
        $this->command->info("  • student.password-reset@{$d} — password reset token");
        $this->command->info("  • student.soft-deleted@{$d} — soft deleted");
        $this->command->newLine();
    }
}
