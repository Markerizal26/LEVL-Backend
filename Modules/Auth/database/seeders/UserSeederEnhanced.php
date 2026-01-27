<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;

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
    }

    private function createDemoUsers(): void
    {
        $this->command->info("  🎭 Creating demo users...");

        $demos = [
            [
                'name' => 'Super Admin Demo',
                'username' => 'superadmin_demo',
                'email' => 'superadmin.demo@test.com',
                'role' => 'Superadmin',
                'status' => UserStatus::Active,
                'verified' => true,
            ],
            [
                'name' => 'Admin Demo',
                'username' => 'admin_demo',
                'email' => 'admin.demo@test.com',
                'role' => 'Admin',
                'status' => UserStatus::Active,
                'verified' => true,
            ],
            [
                'name' => 'Instructor Demo',
                'username' => 'instructor_demo',
                'email' => 'instructor.demo@test.com',
                'role' => 'Instructor',
                'status' => UserStatus::Active,
                'verified' => true,
            ],
            [
                'name' => 'Student Demo',
                'username' => 'student_demo',
                'email' => 'student.demo@test.com',
                'role' => 'Student',
                'status' => UserStatus::Active,
                'verified' => true,
            ],
        ];

        $created = 0;
        foreach ($demos as $demo) {
            if (!User::where('email', $demo['email'])->exists()) {
                $user = $this->createUserWithProfile($demo);
                $this->demoUsers[] = $user;
                $created++;
            }
        }

        $this->command->info("    ✓ Created {$created} demo users");
    }

    private function createSpecialStatusUsers(): void
    {
        $this->command->info("  🔧 Creating special status users...");

        $specialCases = [
            [
                'name' => 'Email Unverified Student',
                'username' => 'unverified_student',
                'email' => 'unverified.student@test.com',
                'role' => 'Student',
                'status' => UserStatus::Pending,
                'verified' => false,
                'description' => 'User with unverified email',
            ],
            [
                'name' => 'Password Not Set Student',
                'username' => 'no_password_student',
                'email' => 'no.password.student@test.com',
                'role' => 'Student',
                'status' => UserStatus::Active,
                'verified' => true,
                'is_password_set' => false,
                'description' => 'User registered via social login, no password set',
            ],
            [
                'name' => 'Inactive Student',
                'username' => 'inactive_student',
                'email' => 'inactive.student@test.com',
                'role' => 'Student',
                'status' => UserStatus::Inactive,
                'verified' => true,
                'description' => 'Inactive user account',
            ],
            [
                'name' => 'Banned Student',
                'username' => 'banned_student',
                'email' => 'banned.student@test.com',
                'role' => 'Student',
                'status' => UserStatus::Banned,
                'verified' => true,
                'description' => 'Banned user account',
            ],
            [
                'name' => 'Pending Email Change Student',
                'username' => 'email_change_pending',
                'email' => 'email.change.student@test.com',
                'role' => 'Student',
                'status' => UserStatus::Active,
                'verified' => true,
                'description' => 'User with pending email change request',
            ],
            [
                'name' => 'Deletion Pending Student',
                'username' => 'deletion_pending',
                'email' => 'deletion.pending@test.com',
                'role' => 'Student',
                'status' => UserStatus::Active,
                'verified' => true,
                'description' => 'User with pending account deletion request',
            ],
            [
                'name' => 'Password Reset Pending Student',
                'username' => 'password_reset_pending',
                'email' => 'password.reset.student@test.com',
                'role' => 'Student',
                'status' => UserStatus::Active,
                'verified' => true,
                'description' => 'User with pending password reset',
            ],
            [
                'name' => 'Recently Deleted Student',
                'username' => 'soft_deleted_student',
                'email' => 'soft.deleted.student@test.com',
                'role' => 'Student',
                'status' => UserStatus::Active,
                'verified' => true,
                'soft_delete' => true,
                'description' => 'Soft deleted user (can be restored)',
            ],
        ];

        $created = 0;
        foreach ($specialCases as $special) {
            if (!User::where('email', $special['email'])->withTrashed()->exists()) {
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
                        'email_verified_at' => $verified ? now()->subDays(rand(1, 365)) : null,
                        'is_password_set' => $role !== 'Student' ? fake()->boolean(80) : true,
                    ])
                    ->raw();

                if (User::where('email', $attributes['email'])
                    ->orWhere('username', $attributes['username'])
                    ->withTrashed()
                    ->exists()) {
                    continue;
                }

                $user = User::create($attributes);
                $users->push($user);
            }

            foreach ($users as $user) {
                if (!$user->hasRole($role)) {
                    $user->assignRole($role);
                }
            }

            $this->batchCreatePrivacySettings($users, $role);
            $this->batchCreateUserActivities($users, $status);

            $created += $users->count();
            
            if ($chunks > 1) {
                $this->command->info("      → Chunk " . ($chunk + 1) . "/{$chunks}: {$users->count()} users");
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
            'email_verified_at' => ($data['verified'] ?? true) ? now() : null,
            'is_password_set' => $data['is_password_set'] ?? true,
            'account_status' => 'active',
            'phone' => fake()->e164PhoneNumber(),
            'bio' => fake()->paragraph(),
        ]);

        $user->assignRole($data['role']);

        DB::table('profile_privacy_settings')->insert([
            'user_id' => $user->id,
            'profile_visibility' => 'public',
            'show_email' => true,
            'show_phone' => true,
            'show_activity_history' => true,
            'show_achievements' => true,
            'show_statistics' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function batchCreatePrivacySettings($users, string $role): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $privacySettings = $users->map(function ($user) use ($role) {
            $privacyVisibility = match (true) {
                $role === 'Student' && fake()->boolean(60) => 'private',
                $role === 'Student' && fake()->boolean(50) => 'friends_only',
                default => 'public',
            };

            return [
                'user_id' => $user->id,
                'profile_visibility' => $privacyVisibility,
                'show_email' => fake()->boolean(),
                'show_phone' => fake()->boolean(),
                'show_activity_history' => true,
                'show_achievements' => true,
                'show_statistics' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        DB::table('profile_privacy_settings')->insertOrIgnore($privacySettings);
    }

    private function batchCreateUserActivities($users, UserStatus $status): void
    {
        if (!DB::getSchemaBuilder()->hasTable('user_activities') || $users->isEmpty()) {
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
                    UserStatus::Active => rand(10, 30),
                    UserStatus::Pending => 0,
                    UserStatus::Inactive => rand(2, 5),
                    UserStatus::Banned => 1,
                    default => 0,
                };

                for ($i = 0; $i < $activityCount; $i++) {
                    $activities[] = [
                        'user_id' => $user->id,
                        'activity_type' => fake()->randomElement($activityTypes),
                        'activity_data' => json_encode([
                            'title' => fake()->sentence(),
                            'description' => fake()->sentence(),
                            'points' => fake()->numberBetween(10, 100),
                        ]),
                        'related_type' => fake()->randomElement([null, 'Course', 'Lesson', 'Assignment']),
                        'related_id' => fake()->boolean(60) ? fake()->numberBetween(1, 100) : null,
                        'created_at' => now()->subDays(rand(1, 90)),
                    ];
                }
            }

            if (!empty($activities)) {
                foreach (array_chunk($activities, 500) as $chunk) {
                    DB::table('user_activities')->insertOrIgnore($chunk);
                }
            }
        } catch (\Exception $e) {
            $this->command->warn("    ⚠️  Could not create activities: " . $e->getMessage());
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
