<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;
use Modules\Schemes\Models\Lesson;

class AssignmentAndSubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates comprehensive assignment and submission data:
     * - 5-8 assignments per lesson
     * - 50-70% students submit assignments
     * - Multiple submissions per student (resubmissions)
     * - Various submission statuses (draft, submitted, graded)
     */
    public function run(): void
    {
        \DB::connection()->disableQueryLog();
        
        echo "Seeding assignments and submissions...\n";

        // ✅ Use raw SQL for counts (minimal memory)
        $lessonCount = \DB::table('lessons')->count();
        $instructorIds = \DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'Instructor')
            ->pluck('users.id')
            ->toArray();

        if ($lessonCount === 0) {
            echo "⚠️  No lessons found. Skipping assignment seeding.\n";
            return;
        }

        if (empty($instructorIds)) {
            echo "⚠️  No instructors found. Skipping assignment seeding.\n";
            return;
        }

        echo "Creating assignments for $lessonCount lessons...\n";
        
        // ✅ STEP 1: Create assignments using cursor (memory efficient)
        $assignmentCount = 0;
        $assignments = [];
        $assignmentBatchSize = 300; // Smaller batch

        foreach (\DB::table('lessons')->orderBy('id')->cursor() as $lesson) {
            $assignmentsPerLesson = rand(3, 5); // REDUCED from 5-8 to 3-5
            $instructorId = $instructorIds[array_rand($instructorIds)];

            for ($i = 0; $i < $assignmentsPerLesson; $i++) {
                $assignments[] = [
                    'lesson_id' => $lesson->id,
                    'title' => fake()->sentence(4),
                    'description' => fake()->text(100),
                    'created_by' => $instructorId,
                    'max_score' => rand(50, 100),
                    'deadline_at' => now()->addDays(rand(7, 30)),
                    'status' => 'published',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $assignmentCount++;
                
                if (count($assignments) >= $assignmentBatchSize) {
                    \DB::table('assignments')->insertOrIgnore($assignments);
                    $assignments = null;
                    unset($assignments);
                    $assignments = [];
                    
                    if ($assignmentCount % 3000 === 0) {
                        gc_collect_cycles();
                        echo "   ✓ Created $assignmentCount assignments\n";
                    }
                }
            }
        }

        if (!empty($assignments)) {
            \DB::table('assignments')->insertOrIgnore($assignments);
            unset($assignments);
        }

        echo "✅ Created $assignmentCount assignments\n";
        gc_collect_cycles();

        // ✅ STEP 2: Create submissions using cursor (maximum memory efficiency)
        echo "Creating submissions...\n";
        $submissionCount = 0;
        $submissions = [];
        $submissionBatchSize = 200; // Even smaller batch
        $processedAssignments = 0;
        
        // Pre-generate answer templates to reduce Faker overhead
        $answerTemplates = [
            'Answer submitted for review.',
            'Completed assignment as per instructions.',
            'Solution provided 150; // Smaller batch
        $processedAssignments = 0;

        // Use cursor instead of chunk to avoid memory buildup
        foreach (\DB::table('assignments')->orderBy('id')->cursor() as $assignment) {
            $processedAssignments++;
            
            // Get course_id via raw SQL join (single value query)
            $courseId = \DB::table('lessons')
                ->join('units', 'lessons.unit_id', '=', 'units.id')
                ->where('lessons.id', $assignment->lesson_id)
                ->value('units.course_id');
            
            if (!$courseId) continue;
            
            // Get LIMITED enrollments for this course (max 20 per assignment)
            $enrollmentIds = \DB::table('enrollments')
                ->where('course_id', $courseId)
                ->where('status', 'active')
                ->inRandomOrder()
                ->limit(20) // REDUCED to 20 students max
                ->pluck('id', 'user_id')
                ->toArray();
            
            if (empty($enrollmentIds)) {
                unset($enrollmentIds);
                continue;
            }
            
            // 40-60% submission rate (REDUCED)
            $submissionRate = rand(40, 60);
            $toSubmit = (int) (count($enrollmentIds) * $submissionRate / 100);
            $selectedEnrollments = array_slice($enrollmentIds, 0, max(1, $toSubmit), true);
            
            foreach ($selectedEnrollments as $userId => $enrollmentId) {
                $statusRandom = rand(1, 100);
                $status = match (true) {
                    $statusRandom <= 30 => 'submitted',
                    $statusRandom <= 70 => 'graded',
                    default => 'draft',
                };
                
                $submissions[] = [
                    'assignment_id' => $assignment->id,
                    'user_id' => $userId,
                    'enrollment_id' => $enrollmentId,
                    'answer_text' => fake()->text(100),
                    'status' => $status,
                    'score' => $status === 'graded' ? rand(0, $assignment->max_score) : null,
                    'submitted_at' => $status !== 'draft' ? now()->subDays(rand(1, 30)) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $submissionCount++;
                
                if (count($submissions) >= $submissionBatchSize) {
                    \DB::table('submissions')->insertOrIgnore($submissions);
                    
                    // Explicit memory cleanup
                    $submissions = null;
                    unset($submissions);
                    $submissions = [];
                    
                    // More aggressive GC
                    if ($submissionCount % 300 === 0) {
                        gc_collect_cycles();
                        echo "  ✅ Inserted $submissionCount submissions\n";
                    }
                }
            }
            
            // Explicit cleanup
            unset($enrollmentIds, $selectedEnrollments);
            
            // GC every 20 assignments (more aggressive)
            if ($processedAssignments % 20
            unset($submissions);
        }

        echo "✅ Assignment and submission seeding completed!\n";
        echo "Created $assignmentCount assignments with $submissionCount submissions\n";
        
        gc_collect_cycles();
        \DB::connection()->enableQueryLog();
    }
}
