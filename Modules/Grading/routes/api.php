<?php

use Illuminate\Support\Facades\Route;
use Modules\Grading\Http\Controllers\AppealController;
use Modules\Grading\Http\Controllers\GradingController;

Route::middleware(['auth:api'])->prefix('v1')->group(function () {
    // =========================================================================
    // Appeals (Requirements 17.1, 17.3, 17.4, 17.5)
    // =========================================================================

    // Pending appeals for instructors (Requirements 17.3)
    Route::get('appeals/pending', [AppealController::class, 'pending'])
        ->name('appeals.pending');

    // Appeal details
    Route::get('appeals/{appeal}', [AppealController::class, 'show'])
        ->name('appeals.show');

    // Appeal approval (Requirements 17.4)
    Route::post('appeals/{appeal}/approve', [AppealController::class, 'approve'])
        ->name('appeals.approve');

    // Appeal denial (Requirements 17.5)
    Route::post('appeals/{appeal}/deny', [AppealController::class, 'deny'])
        ->name('appeals.deny');

    // Appeal submission for a submission (Requirements 17.1, 17.2)
    Route::post('submissions/{submission}/appeals', [AppealController::class, 'submit'])
        ->name('appeals.submit');
    // Grading Queue (Requirements 10.1, 10.2, 10.3, 10.4)
    Route::get('grading/queue', [GradingController::class, 'queue'])
        ->name('grading.queue');

    // Bulk Operations (Requirements 26.2, 26.4, 26.5)
    Route::post('grading/bulk-release', [GradingController::class, 'bulkReleaseGrades'])
        ->name('grading.bulk-release');
    Route::post('grading/bulk-feedback', [GradingController::class, 'bulkApplyFeedback'])
        ->name('grading.bulk-feedback');

    // Submission-specific grading endpoints
    Route::prefix('submissions/{submission}')->group(function () {
        // Auto-grading (Requirements 3.5, 3.6, 3.7)
        Route::post('auto-grade', [GradingController::class, 'autoGrade'])
            ->name('grading.auto-grade');

        // Manual grading (Requirements 10.1, 12.1, 12.2)
        Route::post('manual-grade', [GradingController::class, 'manualGrade'])
            ->name('grading.manual-grade');

        // Draft grades (Requirements 11.1, 11.2, 11.3)
        Route::post('draft-grade', [GradingController::class, 'saveDraftGrade'])
            ->name('grading.save-draft');
        Route::get('draft-grade', [GradingController::class, 'getDraftGrade'])
            ->name('grading.get-draft');

        // Grade override (Requirements 16.1, 16.2, 16.3)
        Route::post('override-grade', [GradingController::class, 'overrideGrade'])
            ->name('grading.override');

        // Grade release (Requirements 14.6)
        Route::post('release-grade', [GradingController::class, 'releaseGrade'])
            ->name('grading.release');

        // Return to queue (Requirements 10.6)
        Route::post('return-to-queue', [GradingController::class, 'returnToQueue'])
            ->name('grading.return-to-queue');

        // Grading status (Requirements 11.4, 11.5)
        Route::get('grading-status', [GradingController::class, 'gradingStatus'])
            ->name('grading.status');
    });
});
