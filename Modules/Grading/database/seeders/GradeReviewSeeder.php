<?php

namespace Modules\Grading\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Grading\Models\Grade;
use Modules\Grading\Models\GradeReview;
use Modules\Grading\Enums\GradeStatus;

class GradeReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates comprehensive grade review data:
     * - Reviews for grades with different statuses
     * - Different review statuses (pending, approved, rejected)
     * - Random assignment of reviewers
     */
    public function run(): void
    {
        echo "Seeding grade reviews...\n";

        // ✅ Pre-fetch all instructors (don't query per review)
        $instructorIds = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Instructor', 'Admin']);
        })->pluck('id')->toArray();

        // Check if any grades exist to start with
        if (!Grade::exists()) {
            echo "⚠️  No grades found. Skipping grade review seeding.\n";
            return;
        }

        if (empty($instructorIds)) {
            echo "⚠️  No instructors found. Skipping grade review seeding.\n";
            return;
        }

        $reviewCount = 0;

        // ✅ Process grades in chunks to save memory
        Grade::with(['user', 'grader'])->chunkById(1000, function ($grades) use ($instructorIds, &$reviewCount) {
            $reviews = [];

            foreach ($grades as $grade) {
                // ✅ 10-15% of grades get reviewed
                if (rand(1, 100) > 15) {
                    continue;
                }

                $requesterId = $grade->user_id;
                
                // Determine review status randomly to include all possible statuses
                $statusRandom = rand(1, 100);
                $reviewStatus = match (true) {
                    $statusRandom <= 50 => 'pending',      // 50% of reviews are 'pending'
                    $statusRandom <= 80 => 'approved',     // 30% of reviews are 'approved'
                    default => 'rejected',                 // 20% of reviews are 'rejected'
                };

                $reviewerId = in_array($reviewStatus, ['approved', 'rejected']) 
                    ? $instructorIds[array_rand($instructorIds)] 
                    : null;

                $reviews[] = [
                    'grade_id' => $grade->id,
                    'requested_by' => $requesterId,
                    'reason' => fake()->paragraph(),
                    'response' => in_array($reviewStatus, ['approved', 'rejected']) ? fake()->paragraph() : null,
                    'reviewed_by' => $reviewerId,
                    'status' => $reviewStatus,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $reviewCount++;
            }

            // ✅ Batch insert reviews
            if (!empty($reviews)) {
                \Illuminate\Support\Facades\DB::table('grade_reviews')->insertOrIgnore($reviews);
            }

            // Explicitly clear arrays to free memory (though partial in loop scope)
            unset($reviews);

            echo "Processed chunk. Grade reviews: $reviewCount\n";
        });

        echo "✅ Grade review seeding completed!\n";
        echo "Created $reviewCount grade reviews\n";
    }
}