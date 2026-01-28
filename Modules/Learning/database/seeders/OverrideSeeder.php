<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Override;
use Modules\Learning\Enums\OverrideType;

class OverrideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates comprehensive override data:
     * - Deadline overrides (extended deadlines)
     * - Attempts overrides (additional attempts)
     * - Prerequisite overrides (bypass prerequisites)
     */
    public function run(): void
    {
        \DB::connection()->disableQueryLog();
        
        echo "Seeding overrides...\n";

        // Check if we have users and assignments to link to
        $instructorIds = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Instructor', 'Admin']);
        })->pluck('id');

        $assignmentIds = Assignment::pluck('id');
        $studentIds = User::role('Student')->pluck('id');

        if ($instructorIds->isEmpty()) {
            echo "⚠️  No instructors found. Skipping override seeding.\n";
            return;
        }

        if ($assignmentIds->isEmpty()) {
            echo "⚠️  No assignments found. Skipping override seeding.\n";
            return;
        }

        if ($studentIds->isEmpty()) {
            echo "⚠️  No students found. Skipping override seeding.\n";
            return;
        }

        $overrideCount = 0;

        // Convert collections to arrays for easier random selection
        $instructorIds = $instructorIds->toArray();
        $assignmentIds = $assignmentIds->toArray();
        $studentIds = $studentIds->toArray();

        // Prepare overrides to be inserted
        $overridesToInsert = [];

        // Select a subset of assignments for overrides
        $assignmentsForOverride = array_slice($assignmentIds, 0, min(20, count($assignmentIds)));

        // Create overrides for selected assignments
        foreach ($assignmentsForOverride as $assignmentId) {
            // Create 1-3 overrides per assignment
            $numOverrides = rand(1, 3);

            for ($i = 0; $i < $numOverrides; $i++) {
                $studentId = $studentIds[array_rand($studentIds)];
                $instructorId = $instructorIds[array_rand($instructorIds)];

                // Randomly select override type
                $overrideTypes = [
                    OverrideType::Deadline,
                    OverrideType::Attempts,
                    OverrideType::Prerequisite,
                ];

                $type = $overrideTypes[array_rand($overrideTypes)];

                // Prepare override data based on type
                $overrideData = [
                    'assignment_id' => $assignmentId,
                    'student_id' => $studentId,
                    'grantor_id' => $instructorId,
                    'type' => $type,
                    'reason' => fake()->sentence(),
                    'granted_at' => now(),
                    'expires_at' => null, // Will be set based on type below
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Add type-specific values
                switch ($type) {
                    case OverrideType::Deadline:
                        $overrideData['value'] = json_encode([
                            'extended_deadline' => now()->addDays(rand(1, 14))->toISOString(),
                        ], JSON_UNESCAPED_SLASHES);
                        $overrideData['expires_at'] = now()->addDays(rand(15, 30));
                        break;

                    case OverrideType::Attempts:
                        $overrideData['value'] = json_encode([
                            'additional_attempts' => rand(1, 3),
                        ], JSON_UNESCAPED_SLASHES);
                        $overrideData['expires_at'] = now()->addDays(rand(30, 60));
                        break;

                    case OverrideType::Prerequisite:
                        $overrideData['value'] = json_encode([
                            'bypassed_prerequisites' => [], // Will be filled later if needed
                        ], JSON_UNESCAPED_SLASHES);
                        $overrideData['expires_at'] = now()->addDays(rand(7, 21));
                        break;
                }

                $overridesToInsert[] = $overrideData;
                $overrideCount++;
            }
        }

        // Batch insert all overrides
        if (!empty($overridesToInsert)) {
            foreach (array_chunk($overridesToInsert, 1000) as $chunk) {
                \DB::table('overrides')->insertOrIgnore($chunk);
            }
        }

        echo "✅ Override seeding completed!\n";
        echo "Created $overrideCount overrides\n";
        
        gc_collect_cycles();
        \DB::connection()->enableQueryLog();
    }
}