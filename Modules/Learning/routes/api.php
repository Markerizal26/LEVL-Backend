<?php

use Illuminate\Support\Facades\Route;
use Modules\Learning\Http\Controllers\AssignmentController;
use Modules\Learning\Http\Controllers\SubmissionController;

Route::middleware(['auth:api'])->prefix('v1')->scopeBindings()->group(function () {
    // Assignment routes - authenticated users with enrollment checks via policy
    Route::get('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/assignments', [AssignmentController::class, 'index'])
        ->name('lessons.assignments.index');

    Route::get('assignments/{assignment}', [AssignmentController::class, 'show'])
        ->middleware('can:view,assignment')
        ->name('assignments.show');

    Route::get('assignments/{assignment}/questions', [AssignmentController::class, 'listQuestions'])
        ->middleware('can:view,assignment')
        ->name('assignments.questions.index');

    Route::get('assignments/{assignment}/check-prerequisites', [AssignmentController::class, 'checkPrerequisites'])
        ->name('assignments.check-prerequisites');

    Route::get('assignments/{assignment}/check-deadline', [SubmissionController::class, 'checkDeadline'])
        ->name('assignments.check-deadline');

    Route::get('assignments/{assignment}/check-attempts', [SubmissionController::class, 'checkAttempts'])
        ->name('assignments.check-attempts');

    Route::get('assignments/{assignment}/my-submissions', [SubmissionController::class, 'mySubmissions'])
        ->name('assignments.my-submissions');

    Route::get('assignments/{assignment}/highest-submission', [SubmissionController::class, 'highestSubmission'])
        ->name('assignments.highest-submission');

    // Assignment management routes (Admin, Instructor, Superadmin only)
    Route::middleware(['role:Superadmin|Admin|Instructor'])->group(function () {
        Route::post('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/assignments', [AssignmentController::class, 'store'])
            ->name('lessons.assignments.store');

        Route::put('assignments/{assignment}', [AssignmentController::class, 'update'])
            ->middleware('can:update,assignment')
            ->name('assignments.update');

        Route::delete('assignments/{assignment}', [AssignmentController::class, 'destroy'])
            ->middleware('can:delete,assignment')
            ->name('assignments.destroy');

        Route::put('assignments/{assignment}/publish', [AssignmentController::class, 'publish'])
            ->middleware('can:update,assignment')
            ->name('assignments.publish');

        Route::put('assignments/{assignment}/unpublish', [AssignmentController::class, 'unpublish'])
            ->middleware('can:update,assignment')
            ->name('assignments.unpublish');

        Route::post('assignments/{assignment}/questions', [AssignmentController::class, 'addQuestion'])
            ->middleware('can:update,assignment')
            ->name('assignments.questions.store');

        Route::put('assignments/{assignment}/questions/{question}', [AssignmentController::class, 'updateQuestion'])
            ->middleware('can:update,assignment')
            ->name('assignments.questions.update');

        Route::delete('assignments/{assignment}/questions/{question}', [AssignmentController::class, 'deleteQuestion'])
            ->middleware('can:update,assignment')
            ->name('assignments.questions.destroy');

        Route::get('assignments/{assignment}/overrides', [AssignmentController::class, 'listOverrides'])
            ->middleware('can:viewOverrides,assignment')
            ->name('assignments.overrides.index');

        Route::post('assignments/{assignment}/overrides', [AssignmentController::class, 'grantOverride'])
            ->middleware('can:grantOverride,assignment')
            ->name('assignments.overrides.store');

        Route::post('assignments/{assignment}/duplicate', [AssignmentController::class, 'duplicate'])
            ->middleware('can:duplicate,assignment')
            ->name('assignments.duplicate');

        Route::get('assignments/{assignment}/submissions', [SubmissionController::class, 'index'])
            ->name('assignments.submissions.index');

        Route::get('submissions/search', [SubmissionController::class, 'search'])
            ->name('submissions.search');
    });

    // Submission routes - students and authorized users
    Route::post('assignments/{assignment}/submissions', [SubmissionController::class, 'store'])
        ->name('assignments.submissions.store');

    Route::post('assignments/{assignment}/submissions/start', [SubmissionController::class, 'start'])
        ->name('assignments.submissions.start');

    Route::get('submissions/{submission}', [SubmissionController::class, 'show'])
        ->middleware('can:view,submission')
        ->name('submissions.show');

    Route::put('submissions/{submission}', [SubmissionController::class, 'update'])
        ->middleware('can:update,submission')
        ->name('submissions.update');

    Route::post('submissions/{submission}/submit', [SubmissionController::class, 'submit'])
        ->middleware('can:update,submission')
        ->name('submissions.submit');

    // Grading route (Admin, Instructor, Superadmin only)
    Route::post('submissions/{submission}/grade', [SubmissionController::class, 'grade'])
        ->middleware(['role:Superadmin|Admin|Instructor', 'can:grade,submission'])
        ->name('submissions.grade');
});
