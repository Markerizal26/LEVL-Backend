<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Ensures all users have complete profile data:
     * - Privacy settings
     * - User activities based on role and enrollments
     * - Activity tracking for learning progress
     */
    public function run(): void
    {
        echo "Seeding profile data for all users...\n";

        // ✅ Eager load relationships to avoid N+1
        $users = User::with('privacySettings', 'enrollments', 'roles')
            ->whereNull('deleted_at')
            ->get();

        if ($users->isEmpty()) {
            echo "⚠️  No users found. Skipping profile seeding.\n";
            return;
        }

        $privacyCount = 0;
        $activityCount = 0;

        // ✅ Batch create missing privacy settings
        $privacySettings = [];
        foreach ($users as $user) {
            if (!$user->privacySettings) {
                $visibility = match (true) {
                    $user->roles->whereIn('name', ['Superadmin', 'Admin', 'Instructor'])->count() > 0 => ProfilePrivacySetting::VISIBILITY_PUBLIC,
                    $user->hasRole('Student') => fake()->randomElement([
                        ProfilePrivacySetting::VISIBILITY_PUBLIC,
                        ProfilePrivacySetting::VISIBILITY_PRIVATE,
                    ]),
                    default => ProfilePrivacySetting::VISIBILITY_PUBLIC,
                };

                $privacySettings[] = [
                    'user_id' => $user->id,
                    'profile_visibility' => $visibility,
                    'show_email' => fake()->boolean(20),
                    'show_phone' => fake()->boolean(10),
                    'show_activity_history' => true,
                    'show_achievements' => true,
                    'show_statistics' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $privacyCount++;
            }
        }

        // ✅ Batch insert privacy settings
        if (!empty($privacySettings)) {
            \Illuminate\Support\Facades\DB::table('profile_privacy_settings')->insertOrIgnore($privacySettings);
        }

        // ✅ Create user activities in batch for active users
        $activityCount = $this->createUserActivitiesBatch(
            $users->filter(fn ($user) => $user->status === 'active')
        );

        echo "✅ Created $privacyCount privacy settings\n";
        echo "✅ Created $activityCount user activities\n";
        echo "✅ Profile seeding completed!\n";
    }

    /**
     * Batch create user activities for active users
     */
    private function createUserActivitiesBatch($users): int
    {
        // Skip if user_activities table doesn't exist
        if (!\Illuminate\Support\Facades\Schema::hasTable('user_activities')) {
            return 0;
        }

        if ($users->isEmpty()) {
            return 0;
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
            $totalCount = 0;

            foreach ($users as $user) {
                // Students get more activities if they have enrollments
                if ($user->hasRole('Student')) {
                    $enrollmentCount = $user->enrollments->count();
                    $numActivities = $enrollmentCount > 0 ? fake()->numberBetween(10, 30) : fake()->numberBetween(0, 5);
                } else {
                    $numActivities = fake()->numberBetween(5, 20);
                }

                for ($i = 0; $i < $numActivities; $i++) {
                    $activities[] = [
                        'user_id' => $user->id,
                        'activity_type' => fake()->randomElement($activityTypes),
                        'activity_data' => json_encode([
                            'title' => fake()->sentence(),
                            'description' => fake()->paragraph(),
                            'points' => fake()->numberBetween(10, 100),
                        ]),
                        'related_type' => fake()->randomElement([null, 'Course', 'Lesson', 'Quiz', 'Assignment']),
                        'related_id' => fake()->boolean(60) ? fake()->numberBetween(1, 100) : null,
                        'created_at' => now()->subDays(rand(1, 90)),
                    ];
                    $totalCount++;
                }
            }

            // ✅ Batch insert instead of individual creates
            if (!empty($activities)) {
                \Illuminate\Support\Facades\DB::table('user_activities')->insertOrIgnore($activities);
            }

            return $totalCount;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
    

