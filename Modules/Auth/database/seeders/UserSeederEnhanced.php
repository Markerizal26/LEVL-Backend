<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use App\Support\SeederDate;
use App\Support\RealisticSeederContent;
use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;
use Multiavatar\Multiavatar;

class UserSeederEnhanced extends Seeder
{
    private const CHUNK_SIZE = 100;

    private array $demoUsers = [];

    private array $specialUsers = [];

    public function run(): void
    {
        $this->command->info("\n👥 Creating users with realistic data...");

        $this->createDemoUsers();
        $this->createSpecialStatusUsers();

        $this->createUsersByRole('Superadmin', 50);
        $this->createUsersByRole('Admin', 100);
        $this->createUsersByRole('Instructor', 200);
        $this->createUsersByRole('Student', 650);

        $totalUsers = User::count();
        $this->command->info("\n✅ User seeding completed!");
        $this->command->info("   📊 Total users created: {$totalUsers}");
        $this->printUserSummary();
        $this->verifyAvatars();
    }

    private function createDemoUsers(): void
    {
        $this->command->info('  🎭 Creating demo users...');

        $demos = [
            [
                'name' => 'Super Admin Demo',
                'username' => 'superadmin_demo',
                'email' => RealisticSeederContent::demoEmail('superadmin.demo'),
                'role' => 'Superadmin',
                'status' => UserStatus::Active,
                'verified' => true,
            ],
            [
                'name' => 'Admin Demo',
                'username' => 'admin_demo',
                'email' => RealisticSeederContent::demoEmail('admin.demo'),
                'role' => 'Admin',
                'status' => UserStatus::Active,
                'verified' => true,
            ],
            [
                'name' => 'Instructor Demo',
                'username' => 'instructor_demo',
                'email' => RealisticSeederContent::demoEmail('instructor.demo'),
                'role' => 'Instructor',
                'status' => UserStatus::Active,
                'verified' => true,
            ],
            [
                'name' => 'Student Demo',
                'username' => 'student_demo',
                'email' => RealisticSeederContent::demoEmail('student.demo'),
                'role' => 'Student',
                'status' => UserStatus::Active,
                'verified' => true,
            ],
        ];

        $created = 0;
        foreach ($demos as $demo) {
            if (! User::where('email', $demo['email'])->exists()) {
                $user = $this->createUserWithProfile($demo);
                $this->demoUsers[] = $user;
                $created++;
            }
        }

        $this->command->info("    ✓ Created {$created} demo users");
    }

    private function createSpecialStatusUsers(): void
    {
        $this->command->info('  🔧 Creating special status users...');

        $specialCases = [
            [
                'name' => 'Email Unverified Student',
                'username' => 'unverified_student',
                'email' => RealisticSeederContent::demoEmail('student.unverified'),
                'role' => 'Student',
                'status' => UserStatus::Pending,
                'verified' => false,
                'description' => 'User with unverified email',
            ],
            [
                'name' => 'Password Not Set Student',
                'username' => 'no_password_student',
                'email' => RealisticSeederContent::demoEmail('student.no-password'),
                'role' => 'Student',
                'status' => UserStatus::Active,
                'verified' => true,
                'is_password_set' => false,
                'description' => 'User registered via social login, no password set',
            ],
            [
                'name' => 'Inactive Student',
                'username' => 'inactive_student',
                'email' => RealisticSeederContent::demoEmail('student.inactive'),
                'role' => 'Student',
                'status' => UserStatus::Inactive,
                'verified' => true,
                'description' => 'Inactive user account',
            ],
            [
                'name' => 'Banned Student',
                'username' => 'banned_student',
                'email' => RealisticSeederContent::demoEmail('student.banned'),
                'role' => 'Student',
                'status' => UserStatus::Banned,
                'verified' => true,
                'description' => 'Banned user account',
            ],
            [
                'name' => 'Pending Email Change Student',
                'username' => 'email_change_pending',
                'email' => RealisticSeederContent::demoEmail('student.email-change'),
                'role' => 'Student',
                'status' => UserStatus::Active,
                'verified' => true,
                'description' => 'User with pending email change request',
            ],
            [
                'name' => 'Deletion Pending Student',
                'username' => 'deletion_pending',
                'email' => RealisticSeederContent::demoEmail('student.deletion-pending'),
                'role' => 'Student',
                'status' => UserStatus::Active,
                'verified' => true,
                'description' => 'User with pending account deletion request',
            ],
            [
                'name' => 'Password Reset Pending Student',
                'username' => 'password_reset_pending',
                'email' => RealisticSeederContent::demoEmail('student.password-reset'),
                'role' => 'Student',
                'status' => UserStatus::Active,
                'verified' => true,
                'description' => 'User with pending password reset',
            ],
            [
                'name' => 'Recently Deleted Student',
                'username' => 'soft_deleted_student',
                'email' => RealisticSeederContent::demoEmail('student.soft-deleted'),
                'role' => 'Student',
                'status' => UserStatus::Active,
                'verified' => true,
                'soft_delete' => true,
                'description' => 'Soft deleted user (can be restored)',
            ],
        ];

        $created = 0;
        foreach ($specialCases as $special) {
            if (! User::where('email', $special['email'])->withTrashed()->exists()) {
                $user = $this->createUserWithProfile($special);

                if ($special['soft_delete'] ?? false) {
                    $user->delete();
                }

                $this->specialUsers[] = [
                    'user' => $user,
                    'description' => $special['description'],
                ];
                $created++;
            }
        }

        $this->command->info("    ✓ Created {$created} special status users");
    }

    private function createUsersByRole(string $role, int $count): void
    {
        $this->command->info("\n  👤 Creating {$count} {$role} users...");

        $activeCount = (int) ($count * 0.7);
        $pendingCount = (int) ($count * 0.15);
        $inactiveCount = (int) ($count * 0.1);
        $bannedCount = $count - $activeCount - $pendingCount - $inactiveCount;

        $this->command->info("    • Active: {$activeCount}");
        $this->createUsersWithStatusChunked($role, $activeCount, UserStatus::Active, true);

        $this->command->info("    • Pending: {$pendingCount}");
        $this->createUsersWithStatusChunked($role, $pendingCount, UserStatus::Pending, false);

        $this->command->info("    • Inactive: {$inactiveCount}");
        $this->createUsersWithStatusChunked($role, $inactiveCount, UserStatus::Inactive, true);

        $this->command->info("    • Banned: {$bannedCount}");
        $this->createUsersWithStatusChunked($role, $bannedCount, UserStatus::Banned, true);

        $this->command->info("    ✓ {$count} {$role} users created");
    }

    private function createUsersWithStatusChunked(
        string $role,
        int $count,
        UserStatus $status,
        bool $verified
    ): void {
        if ($count === 0) {
            return;
        }

        $chunks = (int) ceil($count / self::CHUNK_SIZE);
        $created = 0;

        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $chunkSize = min(self::CHUNK_SIZE, $count - $created);
            $users = collect();
            $attempts = 0;
            $maxAttempts = $chunkSize * 3;

            while ($users->count() < $chunkSize && $attempts < $maxAttempts) {
                $attempts++;

                $attributes = UserFactory::new()
                    ->state([
                        'status' => $status->value,
                        'email_verified_at' => $verified ? SeederDate::randomPastDateTimeBetween(1, 180) : null,
                        'is_password_set' => $this->pgsqlBool(true),
                    ])
                    ->raw();

                $seed = crc32($attributes['email']);
                $attributes['is_password_set'] = $this->pgsqlBool($role !== 'Student' ? ($seed % 5 !== 0) : true);
                if ($verified) {
                    $attributes['email_verified_at'] = SeederDate::randomPastDateTimeBetween(1, 180);
                }

                if (User::where('email', $attributes['email'])
                    ->orWhere('username', $attributes['username'])
                    ->withTrashed()
                    ->exists()) {
                    continue;
                }

                
                if (isset($attributes['specialization_id']) && $attributes['specialization_id'] === null) {
                    unset($attributes['specialization_id']);
                }

                $user = User::create($attributes);
                $users->push($user);
            }

            foreach ($users as $user) {
                if (! $user->hasRole($role)) {
                    $user->assignRole($role);
                }
            }

            $this->batchCreateUserActivities($users, $status);

            foreach ($users as $user) {
                $this->attachAvatar($user);
            }

            $created += $users->count();

            if ($chunks > 1) {
                $this->command->info('      → Chunk '.($chunk + 1)."/{$chunks}: {$users->count()} users");
            }
        }
    }

    private function createUserWithProfile(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make('password'),
            'status' => $data['status']->value ?? $data['status'],
            'email_verified_at' => ($data['verified'] ?? true) ? SeederDate::randomPastDateTimeBetween(1, 180) : null,
            'is_password_set' => $this->pgsqlBool($data['is_password_set'] ?? true),
            'phone' => RealisticSeederContent::phoneForIndex(abs(crc32($data['email'])) % 100000 + 1),
            'bio' => RealisticSeederContent::bioForUser(abs(crc32($data['email'])) % 10000 + 1),
        ]);

        $user->assignRole($data['role']);

        $this->attachAvatar($user);

        return $user;
    }

    
    private function attachAvatar(User $user): void
    {
        try {
            $multiavatar = new \Multiavatar;

            
            $svgContent = $multiavatar($user->username, null, null);

            if (empty($svgContent)) {
                throw new \RuntimeException('Multiavatar menghasilkan SVG kosong.');
            }

            $tmpPath = sys_get_temp_dir()."/avatar_{$user->id}_".uniqid().'.svg';
            file_put_contents($tmpPath, $svgContent);

            $user->addMedia($tmpPath)
                ->usingFileName("avatar_{$user->username}.svg")
                ->withCustomProperties(['generated_by' => 'multiavatar'])
                ->toMediaCollection('avatar');

        } catch (\Throwable $e) {
            
            
        }
    }

    private function pgsqlBool(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    private function batchCreateUserActivities($users, UserStatus $status): void
    {
        if (! DB::getSchemaBuilder()->hasTable('user_activities') || $users->isEmpty()) {
            return;
        }

        try {
            $activityTypes = [
                UserActivity::TYPE_ENROLLMENT,
                UserActivity::TYPE_COMPLETION,
                UserActivity::TYPE_SUBMISSION,
                UserActivity::TYPE_ACHIEVEMENT,
                UserActivity::TYPE_BADGE_EARNED,
                UserActivity::TYPE_CERTIFICATE_EARNED,
            ];

            $activities = [];
            foreach ($users as $user) {
                $activityCount = match ($status) {
                    UserStatus::Active => 10 + ($user->id % 21),
                    UserStatus::Pending => 0,
                    UserStatus::Inactive => 2 + ($user->id % 4),
                    UserStatus::Banned => 1,
                    default => 0,
                };

                for ($i = 0; $i < $activityCount; $i++) {
                    $seed = $user->id * 31 + $i;
                    $activities[] = [
                        'user_id' => $user->id,
                        'activity_type' => $activityTypes[$seed % count($activityTypes)],
                        'activity_data' => json_encode([
                            'title' => RealisticSeederContent::activityLogTitle($seed),
                            'description' => RealisticSeederContent::shortSentence($seed + 1),
                            'points' => 10 + ($seed % 90),
                        ]),
                        'related_type' => ($seed % 3 === 0) ? null : ['Course', 'Lesson', 'Assignment'][$seed % 3],
                        'related_id' => ($seed % 5 === 0) ? null : (($seed % 100) + 1),
                        'created_at' => SeederDate::randomPastDateTimeBetween(1, 180),
                    ];
                }
            }

            if (! empty($activities)) {
                foreach (array_chunk($activities, 500) as $chunk) {
                    DB::table('user_activities')->insertOrIgnore($chunk);
                }
            }
        } catch (\Exception $e) {
            $this->command->warn('    ⚠️  Could not create activities: '.$e->getMessage());
        }
    }

    
    private function verifyAvatars(): void
    {
        $totalUsers = User::withTrashed()->count();
        $withAvatar = User::withTrashed()->has('media')->count();
        $withoutAvatar = $totalUsers - $withAvatar;

        $this->command->info("\n🖼️  Avatar Verification:");
        $this->command->info("   ✓ Punya avatar : {$withAvatar} / {$totalUsers}");

        if ($withoutAvatar > 0) {
            $this->command->warn("   ✗ Tanpa avatar  : {$withoutAvatar} user (cek warning ⚠️  di atas)");
        } else {
            $this->command->info('   ✓ Semua user sudah punya avatar!');
        }
    }

    private function printUserSummary(): void
    {
        $this->command->info("\n📋 User Distribution:");

        $roles = ['Superadmin', 'Admin', 'Instructor', 'Student'];
        foreach ($roles as $role) {
            $count = User::role($role)->count();
            $this->command->info("   • {$role}: {$count}");
        }

        $this->command->info("\n📊 Status Distribution:");
        foreach (UserStatus::cases() as $status) {
            $count = User::where('status', $status->value)->count();
            $this->command->info("   • {$status->value}: {$count}");
        }

        $this->command->info("\n🎯 Special Users for Testing:");
        foreach ($this->specialUsers as $special) {
            $this->command->info("   • {$special['description']}");
            $this->command->info("     Email: {$special['user']->email}");
        }
    }
}
