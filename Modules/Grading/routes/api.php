<?php

use Illuminate\Support\Facades\Route;
use Modules\Grading\Http\Controllers\GradingController;

Route::middleware(['auth:api'])->prefix('v1')->group(function () {

    Route::middleware(['role:Superadmin|Admin|Instructor'])->group(function () {
        Route::get('grading', [GradingController::class, 'queue'])->name('grading.index');
        Route::get('grading/quiz-submissions/{quizSubmission}/questions/{questionId}', [GradingController::class, 'showQuizEssayQuestion'])
            ->middleware('can:view,quizSubmission')
            ->name('grading.quiz-essay-question.show');
        Route::post('grading/essays/batch', [GradingController::class, 'batchEssays'])->name('grading.essays.batch');
        Route::get('grading/{submissionId}', [GradingController::class, 'show'])->name('grading.show');
        Route::post('submissions/{submissionId}/grades', [GradingController::class, 'manualGradeUnified'])->name('grading.store');
        Route::put('submissions/{submissionId}/grades/draft', [GradingController::class, 'saveDraftGradeUnified'])->name('grading.save-draft');
        Route::patch('submissions/{submissionId}/grades/release', [GradingController::class, 'releaseGradeUnified'])->name('grading.release');
        Route::post('grading/bulk-release', [GradingController::class, 'bulkReleaseGrades'])->name('grading.bulk.release');
        Route::post('grading/bulk-feedback', [GradingController::class, 'bulkApplyFeedback'])->name('grading.bulk.feedback');
    });

    Route::middleware(['role:Superadmin|Admin|Instructor', 'can:grade,submission'])->prefix('submissions/{submission}/grades')->group(function () {
        Route::get('/', [GradingController::class, 'getGrade'])->name('grading.get');
        Route::patch('return-to-queue', [GradingController::class, 'returnToQueue'])->name('grading.return-to-queue');
        Route::get('status', [GradingController::class, 'gradingStatus'])->name('grading.status');
    });
});
