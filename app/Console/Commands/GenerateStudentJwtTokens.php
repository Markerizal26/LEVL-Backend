<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\JWTAuth;

class GenerateStudentJwtTokens extends Command
{
    protected $signature = 'levl:generate-student-jwt-tokens
        {--count=300 : Number of student accounts to create}
        {--ttl=300 : JWT lifetime in minutes}
        {--output= : Output file path for tokens}
        {--password=password : Password assigned to generated users}';

    protected $description = 'Generate active student accounts and export JWT tokens for load testing';

    public function handle(JWTAuth $jwt): int
    {
        $count = max(1, (int) $this->option('count'));
        $ttl = max(1, (int) $this->option('ttl'));
        $password = (string) $this->option('password');
        $outputPath = $this->resolveOutputPath($count, $ttl);

        $originalTtl = $jwt->factory()->getTTL();
        $tokens = [];

        $this->info("Generating {$count} active student accounts with JWT TTL {$ttl} minutes.");

        try {
            $jwt->factory()->setTTL($ttl);

            for ($index = 1; $index <= $count; $index++) {
                $user = User::factory()->active()->create([
                    'status' => UserStatus::Active->value,
                    'is_password_set' => true,
                    'password' => $password,
                    'email_verified_at' => now(),
                ]);

                $studentRole = Role::findOrCreate('Student', 'api');
                $user->syncRoles([$studentRole]);

                $token = $jwt->fromUser($user);

                $tokens[] = $token;

                $this->line(sprintf(
                    '%3d/%3d | %s | %s | %s',
                    $index,
                    $count,
                    $user->email,
                    $studentRole->name,
                    $token
                ));
            }
        } finally {
            $jwt->factory()->setTTL($originalTtl);
        }

        File::put($outputPath, implode(PHP_EOL, $tokens).PHP_EOL);

        $this->info("Saved {$count} tokens to {$outputPath}");

        return self::SUCCESS;
    }

    private function resolveOutputPath(int $count, int $ttl): string
    {
        $output = (string) $this->option('output');

        if ($output !== '') {
            return $output;
        }

        return storage_path(sprintf('app/levl-student-jwts-%d-ttl%d.txt', $count, $ttl));
    }
}