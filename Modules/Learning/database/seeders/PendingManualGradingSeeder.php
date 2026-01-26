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
     * Updates some existing submissions to have state 'PendingManualGrading'
     * to ensure the /grading endpoint has data to display.
     */
    public function run(): void
    {
        echo "Updating submissions to PendingManualGrading state...\n";

        // Find submissions that are in 'submitted' state but don't have a grade yet
        // and update them to 'pending_manual_grading' state
        $count = Submission::where('state', 'submitted')
            ->whereDoesntHave('grade')
            ->limit(50) // Update only first 50 to avoid overloading
            ->update([
                'state' => SubmissionState::PendingManualGrading->value,
                'status' => 'submitted' // Keep status as submitted
            ]);

        echo "✅ Updated $count submissions to PendingManualGrading state\n";
    }
}