<?php
declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates comprehensive user data with:
     * - 50 Superadmin users
     * - 100 Admin users
     * - 200 Instructor users
     * - 650 Student users
     * Total: 1000 users
     * 
     * With distribution of statuses:
     * - 70% Active
     * - 15% Pending
     * - 10% Inactive
     * - 5% Banned
     */
    public function run(): void
    {
        // Create demo users for testing
        $this->createDemoUsers();

        // Create Superadmin users (50)
        echo "Creating 50 Superadmin users...\n";
        $this->createUsersByRole('Superadmin', 50);

        // Create Admin users (100)
        echo "Creating 100 Admin users...\n";
        $this->createUsersByRole('Admin', 100);

        // Create Instructor users (200)
        echo "Creating 200 Instructor users...\n";
        $this->createUsersByRole('Instructor', 200);

        // Create Student users (650)
        echo "Creating 650 Student users...\n";
        $this->createUsersByRole('Student', 650);

        echo "✅ User seeding completed successfully!\n";
        echo "Total users created: 1000\n";
    }

    /**
     * Create demo users for quick testing
     */
    private function createDemoUsers(): void
    {
        echo "Creating demo users for testing...\n";

        $demoUsers = [
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
            [
                'name' => 'Student Pending Demo',
                'username' => 'student_pending_demo',
                'email' => 'student.pending.demo@test.com',
                'role' => 'Student',
                'status' => UserStatus::Pending,
                'verified' => false,
            ],
            [
                'name' => 'Student Inactive Demo',
                'username' => 'student_inactive_demo',
                'email' => 'student.inactive.demo@test.com',
                'role' => 'Student',
                'status' => UserStatus::Inactive,
                'verified' => true,
            ],
        ];

        $privacySettings = [];
        $createdUsers = collect();

        foreach ($demoUsers as $demoUser) {
            // Check if user already exists by email or username
            $existingUser = User::where('email', $demoUser['email'])
                ->orWhere('username', $demoUser['username'])
                ->first();

            if ($existingUser) {
                echo "  ⚠️  User {$demoUser['username']} already exists, skipping...\n";
                continue;
            }

            $user = User::create([
                'name' => $demoUser['name'],
                'username' => $demoUser['username'],
                'email' => $demoUser['email'],
                'password' => Hash::make('password'),
                'status' => $demoUser['status'],
                'email_verified_at' => $demoUser['verified'] ? now() : null,
                'is_password_set' => true,
                'account_status' => 'active',
                'phone' => fake()->phoneNumber(),
                'bio' => fake()->paragraph(),
            ]);

            // Assign role
            $user->assignRole($demoUser['role']);

            // ✅ Batch privacy settings
            $privacySettings[] = [
                'user_id' => $user->id,
                'profile_visibility' => 'public',
                'show_email' => fake()->boolean(),
                'show_phone' => fake()->boolean(),
                'show_activity_history' => true,
                'show_achievements' => true,
                'show_statistics' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $createdUsers->push($user);
        }

        // ✅ Batch insert privacy settings
        if (!empty($privacySettings)) {
            \Illuminate\Support\Facades\DB::table('profile_privacy_settings')->insertOrIgnore($privacySettings);
        }

        // ✅ Batch create activities for demo users
        if ($createdUsers->isNotEmpty()) {
            $this->createUserActivitiesBatch($createdUsers, UserStatus::Active);
        }

        echo "✅ Demo users created\n";
    }

    /**
     * Create users for a specific role
     */
    private function createUsersByRole(string $role, int $count): void
    {
        // Calculate status distribution
        $activeCount = (int) ($count * 0.7);
        $pendingCount = (int) ($count * 0.15);
        $inactiveCount = (int) ($count * 0.1);
        $bannedCount = $count - $activeCount - $pendingCount - $inactiveCount;

        // Create active users
        $this->createUsersWithStatus($role, $activeCount, UserStatus::Active, true);

        // Create pending users
        $this->createUsersWithStatus($role, $pendingCount, UserStatus::Pending, false);

        // Create inactive users
        $this->createUsersWithStatus($role, $inactiveCount, UserStatus::Inactive, true);

        // Create banned users
        $this->createUsersWithStatus($role, $bannedCount, UserStatus::Banned, true);

        echo "✅ $count $role users created\n";
    }

    private function createUsersWithStatus(
        string $role,
        int $count,
        UserStatus $status,
        bool $verified
    ): void {
        $users = collect();
        $attempts = 0;
        $maxAttempts = $count * 3;

        while ($users->count() < $count && $attempts < $maxAttempts) {
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

        $privacySettings = collect($users)->map(function ($user) use ($role) {
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

        \Illuminate\Support\Facades\DB::table('profile_privacy_settings')->insertOrIgnore($privacySettings);

        $this->createUserActivitiesBatch($users, $status);
    }

    /**
     * Batch create user activities for multiple users
     */
    private function createUserActivitiesBatch($users, UserStatus $status): void
    {
        // Skip if user_activities table doesn't exist
        if (!\Illuminate\Support\Facades\Schema::hasTable('user_activities')) {
            return;
        }

        if ($users->isEmpty()) {
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
                            'description' => fake()->paragraph(),
                            'points' => fake()->numberBetween(10, 100),
                        ]),
                        'related_type' => fake()->randomElement([null, 'Course', 'Lesson', 'Assignment']),
                        'related_id' => fake()->boolean(60) ? fake()->numberBetween(1, 100) : null,
                        'created_at' => now()->subDays(rand(1, 90)),
                    ];
                }
            }

            // ✅ Batch insert instead of individual creates
            if (!empty($activities)) {
                \Illuminate\Support\Facades\DB::table('user_activities')->insertOrIgnore($activities);
            }
        } catch (\Exception $e) {
            // Silently skip if table operations fail
        }
    }
}
