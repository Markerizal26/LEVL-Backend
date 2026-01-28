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
        \DB::connection()->disableQueryLog();
        
        echo "Seeding assignment prerequisites...\n";

        // Check if assignments exist
        $assignmentIds = Assignment::pluck('id');

        if ($assignmentIds->count() < 2) {
            echo "⚠️  Need at least 2 assignments to create prerequisites. Skipping.\n";
            return;
        }

        $prerequisiteCount = 0;

        // Convert to array for easier manipulation
        $assignmentIds = $assignmentIds->toArray();

        // Prepare all prerequisite relationships to be inserted
        $relationshipsToInsert = [];
        $existingRelationships = [];

        // Get all existing relationships to avoid duplicates
        $existing = \DB::table('assignment_prerequisites')
            ->select('assignment_id', 'prerequisite_id')
            ->get();

        foreach ($existing as $rel) {
            $existingRelationships["{$rel->assignment_id}_{$rel->prerequisite_id}"] = true;
        }

        // Create prerequisites for some assignments
        foreach ($assignmentIds as $assignmentId) {
            // Skip 70% of assignments to avoid making everything dependent
            if (rand(1, 100) <= 70) {
                continue;
            }

            // Get other assignment IDs (excluding current assignment)
            $otherAssignmentIds = array_filter($assignmentIds, function($id) use ($assignmentId) {
                return $id != $assignmentId;
            });

            if (empty($otherAssignmentIds)) {
                continue;
            }

            // Select 1-2 prerequisites for this assignment
            $numPrerequisites = rand(1, min(2, count($otherAssignmentIds)));
            shuffle($otherAssignmentIds);
            $selectedPrerequisites = array_slice($otherAssignmentIds, 0, $numPrerequisites);

            foreach ($selectedPrerequisites as $prerequisiteId) {
                $relationshipKey = "{$assignmentId}_{$prerequisiteId}";

                // Check if the relationship already exists
                if (!isset($existingRelationships[$relationshipKey])) {
                    $relationshipsToInsert[] = [
                        'assignment_id' => $assignmentId,
                        'prerequisite_id' => $prerequisiteId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $prerequisiteCount++;
                }
            }
        }

        // Batch insert all relationships
        if (!empty($relationshipsToInsert)) {
            foreach (array_chunk($relationshipsToInsert, 1000) as $chunk) {
                \DB::table('assignment_prerequisites')->insertOrIgnore($chunk);
            }
        }

        echo "✅ Assignment prerequisites seeding completed!\n";
        echo "Created $prerequisiteCount prerequisite relationships\n";
        
        gc_collect_cycles();
        \DB::connection()->enableQueryLog();
    }
}