<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Learning\Models\Assignment;

class AssignmentPrerequisitesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates prerequisite relationships between assignments:
     * - Links assignments that must be completed before others
     * - Ensures some assignments have prerequisites
     */
    public function run(): void
    {
        echo "Seeding assignment prerequisites...\n";

        // Check if assignments exist
        $assignments = Assignment::all();
        
        if ($assignments->count() < 2) {
            echo "⚠️  Need at least 2 assignments to create prerequisites. Skipping.\n";
            return;
        }

        $prerequisiteCount = 0;

        // Create prerequisites for some assignments
        foreach ($assignments as $assignment) {
            // Skip 70% of assignments to avoid making everything dependent
            if (rand(1, 100) <= 70) {
                continue;
            }

            // Pick a random assignment as prerequisite (but not the same one)
            $otherAssignments = $assignments->where('id', '!=', $assignment->id);
            
            if ($otherAssignments->isEmpty()) {
                continue;
            }

            // Select 1-2 prerequisites for this assignment
            $numPrerequisites = rand(1, min(2, $otherAssignments->count()));
            $selectedPrerequisites = $otherAssignments->random($numPrerequisites);

            foreach ($selectedPrerequisites as $prerequisite) {
                // Check if the relationship already exists
                $exists = \DB::table('assignment_prerequisites')
                    ->where('assignment_id', $assignment->id)
                    ->where('prerequisite_id', $prerequisite->id)
                    ->exists();

                if (!$exists) {
                    \DB::table('assignment_prerequisites')->insert([
                        'assignment_id' => $assignment->id,
                        'prerequisite_id' => $prerequisite->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $prerequisiteCount++;
                }
            }
        }

        echo "✅ Assignment prerequisites seeding completed!\n";
        echo "Created $prerequisiteCount prerequisite relationships\n";
    }
}