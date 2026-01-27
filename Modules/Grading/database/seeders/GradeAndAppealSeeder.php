<?php

namespace Modules\Grading\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Grading\Models\Grade;
use Modules\Learning\Models\Submission;
use Modules\Grading\Models\Appeal;
use Modules\Grading\Enums\GradeStatus;
use Modules\Grading\Enums\AppealStatus;

class GradeAndAppealSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates comprehensive grading data:
     * - Create grades for submitted assignments
     * - 70-80% of submitted assignments are graded
     * - 5-10% of graded assignments have appeals
     * - Appeals with different statuses
     */
    public function run(): void
    {
        echo "\n📋 Seeding grades and appeals...\n";

        // ✅ Get instructors using raw SQL for speed
        $instructorIds = \DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('roles.name', ['Instructor', 'Admin'])
            ->distinct()
            ->pluck('users.id')
            ->toArray();

        if (empty($instructorIds)) {
            echo "⚠️  No instructors found. Skipping grading seeding.\n";
            return;
        }

        // Count submissions efficiently
        $totalSubmissions = \DB::table('submissions')
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        if ($totalSubmissions === 0) {
            echo "⚠️  No submissions found. Skipping grading seeding.\n";
            return;
        }

        echo "   📝 Processing $totalSubmissions submissions...\n";
        echo "   👥 Using " . count($instructorIds) . " instructors\n\n";

        $gradeCount = 0;
        $appealCount = 0;
        $chunkNum = 0;
        $chunkSize = 2000;
        $offset = 0;

        // ✅ Use raw SQL for better performance on large datasets
        while (true) {
            $submissions = \DB::table('submissions')
                ->select('submissions.id', 'submissions.user_id', 'submissions.assignment_id')
                ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
                ->whereIn('submissions.status', ['submitted', 'graded'])
                ->select('submissions.id', 'submissions.user_id', 'submissions.assignment_id', 'assignments.max_score')
                ->limit($chunkSize)
                ->offset($offset)
                ->orderBy('submissions.id')
                ->get();

            if ($submissions->isEmpty()) {
                break;
            }

            $chunkNum++;
            $grades = [];
            $appeals = [];
            $chunkSubmissions = 0;

            foreach ($submissions as $submission) {
                $chunkSubmissions++;

                // ✅ 70-80% of submissions get graded
                if (rand(1, 100) > 80) {
                    continue;
                }

                $instructorId = $instructorIds[array_rand($instructorIds)];

                // ✅ Determine grade status randomly
                $statusRandom = rand(1, 100);
                $gradeStatus = match (true) {
                    $statusRandom <= 60 => 'graded',
                    $statusRandom <= 80 => 'pending',
                    default => 'reviewed',
                };

                // ✅ Build grade data
                $grades[] = [
                    'source_id' => $submission->assignment_id,
                    'source_type' => 'assignment',
                    'user_id' => $submission->user_id,
                    'submission_id' => $submission->id,
                    'graded_by' => $instructorId,
                    'score' => $gradeStatus === 'pending' ? 0 : rand(0, $submission->max_score),
                    'max_score' => $submission->max_score,
                    'feedback' => $gradeStatus === 'pending' ? null : fake()->paragraph(),
                    'status' => $gradeStatus,
                    'graded_at' => $gradeStatus === 'pending' ? null : now()->subDays(rand(1, 20)),
                    'released_at' => $gradeStatus === 'pending' ? null : now()->subDays(rand(1, 15)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $gradeCount++;

                // ✅ 5-10% of graded submissions have appeals
                if (rand(1, 100) <= 8) {
                    $statusRandom = rand(1, 100);
                    $status = match (true) {
                        $statusRandom <= 40 => 'pending',
                        $statusRandom <= 70 => 'approved',
                        default => 'denied',
                    };

                    $reviewerId = $status !== 'pending' ? $instructorIds[array_rand($instructorIds)] : null;

                    $appeals[] = [
                        'submission_id' => $submission->id,
                        'student_id' => $submission->user_id,
                        'reviewer_id' => $reviewerId,
                        'reason' => fake()->paragraph(),
                        'status' => $status,
                        'submitted_at' => now()->subDays(rand(1, 10)),
                        'decision_reason' => $status !== 'pending' ? fake()->paragraph() : null,
                        'decided_at' => $status !== 'pending' ? now()->subDays(rand(1, 5)) : null,
                        'supporting_documents' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $appealCount++;
                }
            }

            // ✅ Batch insert grades
            if (!empty($grades)) {
                \DB::table('grades')->insertOrIgnore($grades);
            }

            // ✅ Batch insert appeals
            if (!empty($appeals)) {
                \DB::table('appeals')->insertOrIgnore($appeals);
            }

            echo "      ✓ Chunk $chunkNum: $chunkSubmissions submissions | Created Grades: " . count($grades) . " | Appeals: " . count($appeals) . "\n";

            $offset += $chunkSize;
        }
        echo "\n✅ Grading and appeal seeding completed!\n";
        echo "   📊 Total grades created: $gradeCount\n";
        echo "   📊 Total appeals created: $appealCount\n";
    }
}
