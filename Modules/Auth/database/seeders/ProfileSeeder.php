<?php

namespace Modules\Auth\Database\Seeders;

use App\Support\SeederDate;
use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        \DB::connection()->disableQueryLog();
        ini_set('memory_limit', '1536M');

        echo "Seeding profile data for all users...\n";

        $users = User::with('roles')
            ->whereNull('deleted_at')
            ->get();

        if ($users->isEmpty()) {
            echo "⚠️  No users found. Skipping profile seeding.\n";

            return;
        }

        $activeUserIds = $users->filter(fn ($user) => $user->status === 'active')->pluck('id')->toArray();
        unset($users);
        gc_collect_cycles();

        $activityCount = $this->createUserActivitiesBatch($activeUserIds);

        echo "✅ Created $activityCount user activities\n";

        if ($activityCount === 0) {
            echo "ℹ️  All users already have activity data\n";
        }

        echo "✅ Profile seeding completed!\n";

        gc_collect_cycles();
        \DB::connection()->enableQueryLog();
    }

    private function createUserActivitiesBatch(array $userIds): int
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('user_activities')) {
            return 0;
        }

        if (empty($userIds)) {
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

            $courseIds = \Illuminate\Support\Facades\DB::table('courses')->pluck('id')->all();
            $lessonIds = \Illuminate\Support\Facades\DB::table('lessons')->pluck('id')->all();
            $quizIds = \Illuminate\Support\Facades\DB::table('quizzes')->pluck('id')->all();
            $assignmentIds = \Illuminate\Support\Facades\DB::table('assignments')->pluck('id')->all();

            $pregenTitles = ['Enrolled in course', 'Completed lesson', 'Submitted assignment', 'Earned badge', 'Achieved milestone', 'Started quiz', 'Finished module'];
            $pregenDescriptions = ['Progress made', 'New achievement', 'Course completed', 'Badge earned', 'Activity recorded', 'Learning milestone'];
            $createdAt = SeederDate::randomPastDateTimeBetween(1, 180);

            $activities = [];
            $totalCount = 0;

            foreach ($userIds as $userId) {
                $numActivities = rand(3, 8);

                for ($i = 0; $i < $numActivities; $i++) {
                    $relatedType = [null, 'Course', 'Lesson', 'Quiz', 'Assignment'][array_rand([null, 'Course', 'Lesson', 'Quiz', 'Assignment'])];
                    $relatedId = match ($relatedType) {
                        'Course' => $courseIds !== [] ? $courseIds[array_rand($courseIds)] : null,
                        'Lesson' => $lessonIds !== [] ? $lessonIds[array_rand($lessonIds)] : null,
                        'Quiz' => $quizIds !== [] ? $quizIds[array_rand($quizIds)] : null,
                        'Assignment' => $assignmentIds !== [] ? $assignmentIds[array_rand($assignmentIds)] : null,
                        default => null,
                    };

                    $activities[] = [
                        'user_id' => $userId,
                        'activity_type' => $activityTypes[array_rand($activityTypes)],
                        'activity_data' => json_encode([
                            'title' => $pregenTitles[array_rand($pregenTitles)],
                            'description' => $pregenDescriptions[array_rand($pregenDescriptions)],
                            'points' => rand(10, 100),
                        ]),
                        'related_type' => $relatedType,
                        'related_id' => $relatedId,
                        'created_at' => $createdAt,
                    ];
                    $totalCount++;
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

            return $totalCount;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
