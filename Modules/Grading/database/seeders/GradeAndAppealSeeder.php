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
        echo "Seeding grades and appeals...\n";

        // ✅ Pre-fetch all instructors (don't query per grade)
        $instructorIds = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Instructor', 'Admin']);
        })->pluck('id')->toArray();

        // Check if any submissions exist to start with
        if (!Submission::whereIn('status', ['submitted', 'graded'])->exists()) {
             echo "⚠️  No submissions found. Skipping grading seeding.\n";
             return;
        }

        if (empty($instructorIds)) {
            echo "⚠️  No instructors found. Skipping grading seeding.\n";
            return;
        }

        $gradeCount = 0;
        $appealCount = 0;

        // ✅ Process submissions in chunks to save memory
        Submission::whereIn('status', ['submitted', 'graded'])
            ->with(['assignment', 'user']) // Eager load
            ->chunkById(1000, function ($submissions) use ($instructorIds, &$gradeCount, &$appealCount) {
                $grades = [];
                $appeals = [];

                foreach ($submissions as $submission) {
                    // ✅ 70-80% of submissions get graded
                    if (rand(1, 100) > 80) {
                        continue;
                    }

                    $assignment = $submission->assignment;
                    
                    // Skip if assignment missing (defensive)
                    if (!$assignment) continue;

                    $instructorId = $instructorIds[array_rand($instructorIds)];

                    // ✅ Determine grade status randomly to include all possible statuses
                    $statusRandom = rand(1, 100);
                    $gradeStatus = match (true) {
                        $statusRandom <= 60 => GradeStatus::Graded->value,      // 60% of grades are 'graded'
                        $statusRandom <= 80 => GradeStatus::Pending->value,    // 20% of grades are 'pending'
                        default => GradeStatus::Reviewed->value,               // 20% of grades are 'reviewed'
                    };

                    // ✅ Build grade data
                    $grades[] = [
                        'source_id' => $assignment->id,
                        'source_type' => 'assignment',
                        'user_id' => $submission->user_id,
                        'submission_id' => $submission->id,
                        'graded_by' => $instructorId,
                        'score' => $gradeStatus === GradeStatus::Pending->value ? 0 : rand(0, $assignment->max_score), // Use 0 instead of null for pending grades
                        'max_score' => $assignment->max_score,
                        'feedback' => $gradeStatus === GradeStatus::Pending->value ? null : fake()->paragraph(),
                        'status' => $gradeStatus,
                        'graded_at' => $gradeStatus === GradeStatus::Pending->value ? null : now()->subDays(rand(1, 20)),
                        'released_at' => $gradeStatus === GradeStatus::Pending->value ? null : now()->subDays(rand(1, 15)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $gradeCount++;

                    // ✅ 5-10% of graded submissions have appeals
                    if (rand(1, 100) <= 8) {
                        $statusRandom = rand(1, 100);
                        $status = match (true) {
                            $statusRandom <= 40 => AppealStatus::Pending->value,
                            $statusRandom <= 70 => AppealStatus::Approved->value,
                            default => AppealStatus::Denied->value,
                        };

                        $reviewerId = $status !== AppealStatus::Pending->value 
                            ? $instructorIds[array_rand($instructorIds)] 
                            : null;

                        $appeals[] = [
                            'submission_id' => $submission->id,
                            'student_id' => $submission->user_id,
                            'reviewer_id' => $reviewerId,
                            'reason' => fake()->paragraph(),
                            'status' => $status,
                            'submitted_at' => now()->subDays(rand(1, 10)),
                            'decision_reason' => $status !== AppealStatus::Pending->value ? fake()->paragraph() : null,
                            'decided_at' => $status !== AppealStatus::Pending->value ? now()->subDays(rand(1, 5)) : null,
                            'supporting_documents' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $appealCount++;
                    }
                }

                // ✅ Batch insert grades
                if (!empty($grades)) {
                    \Illuminate\Support\Facades\DB::table('grades')->insertOrIgnore($grades);
                }

                // ✅ Batch insert appeals
                if (!empty($appeals)) {
                    \Illuminate\Support\Facades\DB::table('appeals')->insertOrIgnore($appeals);
                }
                
                // Explicitly clear arrays to free memory (though partial in loop scope)
                unset($grades);
                unset($appeals);
                
                echo "Processed chunk. Grades: $gradeCount, Appeals: $appealCount\n";
            });

        echo "✅ Grading and appeal seeding completed!\n";
        echo "Created $gradeCount grades with $appealCount appeals\n";
    }
}
