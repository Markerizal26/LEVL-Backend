<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Modules\Learning\Models\Submission;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Count submissions by state
$counts = DB::table('submissions')
    ->select('state', DB::raw('COUNT(*) as count'))
    ->groupBy('state')
    ->get();

echo "Submission counts by state:\n";
foreach ($counts as $row) {
    echo "- {$row->state}: {$row->count}\n";
}

// Check specifically for PendingManualGrading
$pendingManualCount = Submission::where('state', 'pending_manual_grading')->count();
echo "\nSubmissions with state 'pending_manual_grading': $pendingManualCount\n";

// Check if there are any submissions at all
$totalSubmissions = Submission::count();
echo "Total submissions: $totalSubmissions\n";