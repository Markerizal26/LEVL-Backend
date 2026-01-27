<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\OtpCode;
use Modules\Auth\Models\User;

class OtpCodeSeeder extends Seeder
{
    private const CHUNK_SIZE = 100;

    public function run(): void
    {
        $this->command->info("\n🔐 Creating OTP codes...");

        $totalCodes = 0;
        
        $totalCodes += $this->createEmailVerificationCodes();
        $totalCodes += $this->createPasswordResetCodes();
        $totalCodes += $this->createEmailChangeCodes();
        $totalCodes += $this->createAccountDeletionCodes();

        $this->command->info("\n✅ Created {$totalCodes} OTP codes");
    }

    private function createEmailVerificationCodes(): int
    {
        $this->command->info("  📧 Creating email verification codes...");
        
        $pendingUsers = User::where('status', 'pending')->get();
        $codes = [];

        foreach ($pendingUsers as $user) {
            $codes[] = [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'user_id' => $user->id,
                'channel' => 'email',
                'provider' => 'mailhog',
                'purpose' => 'register_verification',
                'code' => 'magic',
                'meta' => json_encode([
                    'token_hash' => hash('sha256', \Illuminate\Support\Str::random(16)),
                ]),
                'expires_at' => now()->addMinutes(60),
                'consumed_at' => null,
                'created_at' => now()->subMinutes(rand(5, 30)),
                'updated_at' => now()->subMinutes(rand(5, 30)),
            ];
        }

        $this->batchInsertCodes($codes);
        $this->command->info("    ✓ Created " . count($codes) . " email verification codes");
        
        return count($codes);
    }

    private function createPasswordResetCodes(): int
    {
        $this->command->info("  🔑 Creating password reset codes...");
        
        $activeUserCount = User::where('status', 'active')->count();
        $targetCount = (int) ($activeUserCount * 0.05);
        
        $activeUsers = User::where('status', 'active')
            ->inRandomOrder()
            ->limit($targetCount)
            ->get();

        $codes = [];

        foreach ($activeUsers as $user) {
            $codes[] = [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'user_id' => $user->id,
                'channel' => 'email',
                'provider' => 'mailhog',
                'purpose' => 'password_reset',
                'code' => 'magic',
                'meta' => json_encode([
                    'token_hash' => hash('sha256', \Illuminate\Support\Str::random(64)),
                ]),
                'expires_at' => now()->addHour(),
                'consumed_at' => null,
                'created_at' => now()->subHours(rand(1, 23)),
                'updated_at' => now()->subHours(rand(1, 23)),
            ];
        }

        $this->batchInsertCodes($codes);
        $this->command->info("    ✓ Created " . count($codes) . " password reset codes");
        
        return count($codes);
    }

    private function createEmailChangeCodes(): int
    {
        $this->command->info("  ✉️  Creating email change verification codes...");
        
        $activeUserCount = User::where('status', 'active')->count();
        $targetCount = (int) ($activeUserCount * 0.03);
        
        $users = User::where('status', 'active')
            ->inRandomOrder()
            ->limit($targetCount)
            ->get();

        $codes = [];

        foreach ($users as $user) {
            $firstName = fake()->firstName();
            $lastName = fake()->lastName();
            $newEmail = strtolower($firstName . '.' . $lastName . rand(100, 999)) . '@' . fake()->safeEmailDomain();
            
            $codes[] = [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'user_id' => $user->id,
                'channel' => 'email',
                'provider' => 'mailhog',
                'purpose' => 'email_change_verification',
                'code' => 'magic',
                'meta' => json_encode([
                    'token_hash' => hash('sha256', \Illuminate\Support\Str::random(16)),
                    'new_email' => $newEmail,
                ]),
                'expires_at' => now()->addHour(),
                'consumed_at' => null,
                'created_at' => now()->subHours(rand(1, 12)),
                'updated_at' => now()->subHours(rand(1, 12)),
            ];
        }

        $this->batchInsertCodes($codes);
        $this->command->info("    ✓ Created " . count($codes) . " email change codes");
        
        return count($codes);
    }

    private function createAccountDeletionCodes(): int
    {
        $this->command->info("  🗑️  Creating account deletion codes...");
        
        $activeUserCount = User::where('status', 'active')->count();
        $targetCount = (int) ($activeUserCount * 0.01);
        
        $users = User::where('status', 'active')
            ->inRandomOrder()
            ->limit($targetCount)
            ->get();

        $codes = [];

        foreach ($users as $user) {
            $codes[] = [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'user_id' => $user->id,
                'channel' => 'email',
                'provider' => 'mailhog',
                'purpose' => 'account_deletion',
                'code' => 'magic',
                'meta' => json_encode([
                    'token_hash' => hash('sha256', \Illuminate\Support\Str::random(16)),
                    'reason' => fake()->randomElement([
                        'No longer need the account',
                        'Privacy concerns',
                        'Switching to another platform',
                        'Too many emails',
                    ]),
                ]),
                'expires_at' => now()->addHours(24),
                'consumed_at' => null,
                'created_at' => now()->subHours(rand(1, 6)),
                'updated_at' => now()->subHours(rand(1, 6)),
            ];
        }

        $this->batchInsertCodes($codes);
        $this->command->info("    ✓ Created " . count($codes) . " account deletion codes");
        
        return count($codes);
    }

    private function batchInsertCodes(array $codes): void
    {
        if (empty($codes)) {
            return;
        }

        foreach (array_chunk($codes, self::CHUNK_SIZE) as $chunk) {
            DB::table('otp_codes')->insertOrIgnore($chunk);
        }
    }
}

