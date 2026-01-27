<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Learning\Models\Submission;
use Modules\Learning\Models\SubmissionFile;
use Illuminate\Support\Facades\Storage;

class SubmissionFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates submission file records for file upload assignments:
     * - Links files to submissions that have file upload answers
     * - Creates mock file records in the database
     */
    public function run(): void
    {
        echo "Seeding submission files...\n";

        // Get submission IDs that have file upload assignments
        $fileUploadAssignmentIds = \DB::table('assignment_questions')
            ->where('type', 'file_upload')
            ->pluck('assignment_id')
            ->unique()
            ->toArray();

        if (empty($fileUploadAssignmentIds)) {
            echo "⚠️  No assignments with file upload questions found. Skipping submission file seeding.\n";
            return;
        }

        echo "   📁 Found " . count($fileUploadAssignmentIds) . " assignments with file upload questions\n";

        // Get all submission IDs at once (more efficient than chunk)
        $submissionIds = \DB::table('submissions')
            ->whereIn('assignment_id', $fileUploadAssignmentIds)
            ->pluck('id')
            ->toArray();

        if (empty($submissionIds)) {
            echo "⚠️  No submissions found. Skipping.\n";
            return;
        }

        echo "   📋 Processing " . count($submissionIds) . " submissions...\n";

        $fileCount = 0;
        $filesToInsert = [];
        $processed = 0;

        foreach ($submissionIds as $index => $submissionId) {
            // 60% of file upload submissions will have actual files
            if (rand(1, 100) > 60) {
                $processed++;
                if ($processed % 5000 === 0) {
                    echo "      ✓ Processed $processed/" . count($submissionIds) . "\n";
                }
                continue;
            }

            // Create 1-3 files per eligible submission
            $numFiles = rand(1, 3);

            for ($i = 0; $i < $numFiles; $i++) {
                $filesToInsert[] = [
                    'submission_id' => $submissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $fileCount++;

                // Batch insert when we reach 500 records
                if (count($filesToInsert) >= 500) {
                    \DB::table('submission_files')->insertOrIgnore($filesToInsert);
                    $filesToInsert = [];
                }
            }

            $processed++;
            if ($processed % 5000 === 0) {
                echo "      ✓ Processed $processed/" . count($submissionIds) . " ($fileCount files created)\n";
            }
        }

        // Insert any remaining records
        if (!empty($filesToInsert)) {
            \DB::table('submission_files')->insertOrIgnore($filesToInsert);
        }

        echo "✅ Submission file seeding completed!\n";
        echo "   📊 Created $fileCount submission files\n";
    }
}