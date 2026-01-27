<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\PasswordResetToken;
use Modules\Auth\Models\User;

class PasswordResetTokenSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("\n🔓 Creating password reset tokens...");

        $expiredUsers = User::where('status', 'active')
            ->inRandomOrder()
            ->limit(8)
            ->get();
        $expiredEmails = $expiredUsers->pluck('email')->all();

        $expiredTokens = [];
        foreach ($expiredUsers as $user) {
            $expiredTokens[] = [
                'email' => $user->email,
                'token' => hash('sha256', \Illuminate\Support\Str::random(32)),
                'created_at' => now()->subHours(2),
            ];
        }

        $validUsers = User::where('status', 'active')
            ->whereNotIn('email', $expiredEmails)
            ->inRandomOrder()
            ->limit(15)
            ->get();

        $validTokens = [];
        foreach ($validUsers as $user) {
            $validTokens[] = [
                'email' => $user->email,
                'token' => hash('sha256', \Illuminate\Support\Str::random(32)),
                'created_at' => now()->subMinutes(rand(1, 55)),
            ];
        }

        $allTokens = array_merge($expiredTokens, $validTokens);
        
        foreach ($allTokens as $token) {
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $token['email']],
                $token
            );
        }

        $this->command->info("  ✓ Created " . count($expiredTokens) . " expired tokens (> 1 hour)");
        $this->command->info("  ✓ Created " . count($validTokens) . " valid tokens (< 1 hour)");
        $this->command->info("✅ Total password reset tokens: " . count($allTokens));
    }
}

