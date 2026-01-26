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

        // Check if we have submissions to link to
        $submissions = Submission::with('assignment.questions')->get();

        if ($submissions->isEmpty()) {
            echo "⚠️  No submissions found. Skipping submission file seeding.\n";
            return;
        }

        $fileCount = 0;

        foreach ($submissions as $submission) {
            // Only create files for submissions that have file upload questions
            $hasFileUpload = $submission->assignment->questions
                ->contains(function ($question) {
                    return $question->type->value === 'file_upload';
                });

            if (!$hasFileUpload) {
                continue;
            }

            // 60% of file upload submissions will have actual files
            if (rand(1, 100) > 60) {
                continue;
            }

            // Create 1-3 files per eligible submission
            $numFiles = rand(1, 3);

            for ($i = 0; $i < $numFiles; $i++) {
                SubmissionFile::create([
                    'submission_id' => $submission->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $fileCount++;
            }
        }

        echo "✅ Submission file seeding completed!\n";
        echo "Created $fileCount submission files\n";
    }
}