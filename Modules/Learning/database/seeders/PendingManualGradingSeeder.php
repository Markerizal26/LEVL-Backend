<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Learning\Models\Submission;
use Modules\Learning\Enums\SubmissionState;

class PendingManualGradingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Updates submissions to have state 'PendingManualGrading'
     * to ensure the /grading endpoint has data to display.
     * Uses optimized raw SQL with chunking - no N+1 queries.
     */
    public function run(): void
    {
        echo "\n📋 Seeding submissions with PendingManualGrading state...\n";

        // Count submissions that need updating (using raw SQL for speed)
        $totalToUpdate = \DB::table('submissions as s')
            ->leftJoin('grades', 's.id', '=', 'grades.submission_id')
            ->where('s.state', 'submitted')
            ->whereNull('grades.id')
            ->count();

        if ($totalToUpdate === 0) {
            echo "⚠️  No submissions found for PendingManualGrading state.\n";
            return;
        }

        echo "   📝 Processing $totalToUpdate submissions...\n\n";

        // ✅ Use raw SQL with chunking to update efficiently (no N+1)
        $chunkSize = 2000;
        $offset = 0;
        $chunkNum = 0;
        $totalUpdated = 0;

        while (true) {
            // Get submission IDs that need updating using raw SQL
            $submissionIds = \DB::table('submissions as s')
                ->leftJoin('grades', 's.id', '=', 'grades.submission_id')
                ->where('s.state', 'submitted')
                ->whereNull('grades.id')
                ->select('s.id')
                ->limit($chunkSize)
                ->offset($offset)
                ->orderBy('s.id')
                ->pluck('id')
                ->toArray();

            if (empty($submissionIds)) {
                break;
            }

            $chunkNum++;
            $count = count($submissionIds);

            // ✅ Batch update (no N+1)
            \DB::table('submissions')
                ->whereIn('id', $submissionIds)
                ->update([
                    'state' => SubmissionState::PendingManualGrading->value,
                    'status' => 'submitted',
                    'updated_at' => now(),
                ]);

            $totalUpdated += $count;

            echo "      ✓ Chunk $chunkNum: Updated $count submissions (Total: $totalUpdated/$totalToUpdate)\n";

            $offset += $chunkSize;
        }

        echo "\n✅ Completed! Updated $totalUpdated submissions to PendingManualGrading state\n";
    }
}