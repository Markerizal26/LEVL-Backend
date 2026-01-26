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
        echo "Seeding overrides...\n";

        // Check if we have users and assignments to link to
        $instructors = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Instructor', 'Admin']);
        })->get();

        $assignments = Assignment::all();
        $students = User::role('Student')->get();

        if ($instructors->isEmpty()) {
            echo "⚠️  No instructors found. Skipping override seeding.\n";
            return;
        }

        if ($assignments->isEmpty()) {
            echo "⚠️  No assignments found. Skipping override seeding.\n";
            return;
        }

        if ($students->isEmpty()) {
            echo "⚠️  No students found. Skipping override seeding.\n";
            return;
        }

        $overrideCount = 0;

        // Create overrides for a subset of assignments and students
        foreach ($assignments->random(min(20, $assignments->count())) as $assignment) {
            // Create 1-3 overrides per assignment
            $numOverrides = rand(1, 3);

            for ($i = 0; $i < $numOverrides; $i++) {
                $student = $students->random();
                $instructor = $instructors->random();

                // Randomly select override type
                $overrideTypes = [
                    OverrideType::Deadline,
                    OverrideType::Attempts,
                    OverrideType::Prerequisite,
                ];
                
                $type = $overrideTypes[array_rand($overrideTypes)];

                // Prepare override data based on type
                $overrideData = [
                    'assignment_id' => $assignment->id,
                    'student_id' => $student->id,
                    'grantor_id' => $instructor->id,
                    'type' => $type,
                    'reason' => fake()->sentence(),
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Add type-specific values
                switch ($type) {
                    case OverrideType::Deadline:
                        $overrideData['value'] = [
                            'extended_deadline' => now()->addDays(rand(1, 14))->toISOString(),
                        ];
                        $overrideData['expires_at'] = now()->addDays(rand(15, 30));
                        break;

                    case OverrideType::Attempts:
                        $overrideData['value'] = [
                            'additional_attempts' => rand(1, 3),
                        ];
                        $overrideData['expires_at'] = now()->addDays(rand(30, 60));
                        break;

                    case OverrideType::Prerequisite:
                        $overrideData['value'] = [
                            'bypassed_prerequisites' => [], // Will be filled later if needed
                        ];
                        $overrideData['expires_at'] = now()->addDays(rand(7, 21));
                        break;
                }

                Override::create($overrideData);
                $overrideCount++;
            }
        }

        echo "✅ Override seeding completed!\n";
        echo "Created $overrideCount overrides\n";
    }
}