<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use App\Support\SeederDate;
use App\Support\RealisticSeederContent;
use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;

class UserSeeder extends Seeder
{
    
    public function run(): void
    {
        \DB::connection()->disableQueryLog();

        
        $this->createDemoUsers();

        
        echo "Creating 50 Superadmin users...\n";
        $this->createUsersByRole('Superadmin', 50);

        
        echo "Creating 100 Admin users...\n";
        $this->createUsersByRole('Admin', 100);

        
        echo "Creating 200 Instructor users...\n";
        $this->createUsersByRole('Instructor', 200);

        
        echo "Creating 650 Student users...\n";
        $this->createUsersByRole('Student', 650);

        echo "✅ User seeding completed successfully!\n";
        echo "Total users created: 1000\n";

        gc_collect_cycles();
        \DB::connection()->enableQueryLog();
    }

    
    private function createDemoUsers(): void
    {
        echo "Creating demo users for testing...\n";

        $demoUsers = [
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
            [
                'name' => 'Student Pending Demo',
                'username' => 'student_pending_demo',
                'email' => RealisticSeederContent::demoEmail('student.pending.demo'),
                'role' => 'Student',
                'status' => UserStatus::Pending,
                'verified' => false,
            ],
            [
                'name' => 'Student Inactive Demo',
                'username' => 'student_inactive_demo',
                'email' => RealisticSeederContent::demoEmail('student.inactive.demo'),
                'role' => 'Student',
                'status' => UserStatus::Inactive,
                'verified' => true,
            ],
        ];

        $createdUsers = collect();

        foreach ($demoUsers as $demoUser) {

            $existingUser = User::where('email', $demoUser['email'])
                ->orWhere('username', $demoUser['username'])
                ->first();

            if ($existingUser) {
                echo "  ⚠️  User {$demoUser['username']} already exists, skipping...\n";

                continue;
            }

            $seed = abs(crc32($demoUser['email']));
            $user = User::create([
                'name' => $demoUser['name'],
                'username' => $demoUser['username'],
                'email' => $demoUser['email'],
                'password' => Hash::make('password'),
                'status' => $demoUser['status'],
                'email_verified_at' => $demoUser['verified'] ? SeederDate::randomPastDateTimeBetween(1, 180) : null,
                'is_password_set' => $this->pgsqlBool(true),
                'phone' => RealisticSeederContent::phoneForIndex($seed % 100000 + 1),
                'bio' => RealisticSeederContent::bioForUser($seed % 10000 + 1),
            ]);

            $user->assignRole($demoUser['role']);

            $createdUsers->push($user);
        }


        if ($createdUsers->isNotEmpty()) {
            $this->createUserActivitiesBatch($createdUsers, UserStatus::Active);
        }

        echo "✅ Demo users created\n";
    }

    
    private function createUsersByRole(string $role, int $count): void
    {
        
        $activeCount = (int) ($count * 0.7);
        $pendingCount = (int) ($count * 0.15);
        $inactiveCount = (int) ($count * 0.1);
        $bannedCount = $count - $activeCount - $pendingCount - $inactiveCount;

        
        $this->createUsersWithStatus($role, $activeCount, UserStatus::Active, true);

        
        $this->createUsersWithStatus($role, $pendingCount, UserStatus::Pending, false);

        
        $this->createUsersWithStatus($role, $inactiveCount, UserStatus::Inactive, true);

        
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
                ->exists()) {
                continue;
            }

            $user = User::create($attributes);
            $users->push($user);
        }

        $counter = 0;
        foreach ($users as $user) {
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            $counter++;
            if ($counter % 5000 === 0) {
                gc_collect_cycles();
            }
        }

        $this->createUserActivitiesBatch($users, $status);
    }

    
    private function createUserActivitiesBatch($users, UserStatus $status): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('user_activities')) {
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

            $relatedTypes = [null, 'Course', 'Lesson', 'Assignment'];
            $createdAt = SeederDate::randomPastDateTimeBetween(1, 180);

            $activities = [];
            foreach ($users as $user) {
                $activityCount = match ($status) {
                    UserStatus::Active => 5 + ($user->id % 6),
                    UserStatus::Pending => 0,
                    UserStatus::Inactive => 1 + ($user->id % 3),
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
                        'related_type' => $relatedTypes[$seed % count($relatedTypes)],
                        'related_id' => ($seed % 5 === 0) ? null : (($seed % 100) + 1),
                        'created_at' => $createdAt,
                    ];
                }

                if (count($activities) >= 500) {
                    \Illuminate\Support\Facades\DB::table('user_activities')->insertOrIgnore($activities);
                    $activities = [];
                    gc_collect_cycles();
                }
            }

            if (! empty($activities)) {
                \Illuminate\Support\Facades\DB::table('user_activities')->insertOrIgnore($activities);
            }
        } catch (\Exception $e) {
        }
    }

    private function pgsqlBool(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }
}
