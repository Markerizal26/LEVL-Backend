<?php

use Illuminate\Support\Facades\Route;
use Modules\Learning\Http\Controllers\AssignmentController;
use Modules\Learning\Http\Controllers\SubmissionController;

Route::middleware(['auth:api'])->prefix('v1')->scopeBindings()->group(function () {
    // =========================================================================
    // Assignment Routes (nested under lesson for creation)
    // =========================================================================
    Route::get('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/assignments', [AssignmentController::class, 'index'])
        ->name('lessons.assignments.index');
    Route::post('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/assignments', [AssignmentController::class, 'store'])
        ->middleware('role:Admin|Instructor|Superadmin')
        ->name('lessons.assignments.store');

    // =========================================================================
    // Assignment CRUD Routes (Requirement 1.1)
    // =========================================================================
    Route::get('assignments/{assignment}', [AssignmentController::class, 'show'])
        ->name('assignments.show');
    Route::put('assignments/{assignment}', [AssignmentController::class, 'update'])
        ->middleware('role:Admin|Instructor|Superadmin')
        ->name('assignments.update');
    Route::delete('assignments/{assignment}', [AssignmentController::class, 'destroy'])
        ->middleware('role:Admin|Instructor|Superadmin')
        ->name('assignments.destroy');

    // Assignment Status Routes
    Route::put('assignments/{assignment}/publish', [AssignmentController::class, 'publish'])
        ->middleware('role:Admin|Instructor|Superadmin')
        ->name('assignments.publish');
    Route::put('assignments/{assignment}/unpublish', [AssignmentController::class, 'unpublish'])
        ->middleware('role:Admin|Instructor|Superadmin')
        ->name('assignments.unpublish');

    // =========================================================================
    // Question Management Routes (Requirements 3.1-3.8)
    // =========================================================================
    Route::get('assignments/{assignment}/questions', [AssignmentController::class, 'listQuestions'])
        ->name('assignments.questions.index');
    Route::post('assignments/{assignment}/questions', [AssignmentController::class, 'addQuestion'])
        ->middleware('role:Admin|Instructor|Superadmin')
        ->name('assignments.questions.store');
    Route::put('assignments/{assignment}/questions/{question}', [AssignmentController::class, 'updateQuestion'])
        ->middleware('role:Admin|Instructor|Superadmin')
        ->name('assignments.questions.update');
    Route::delete('assignments/{assignment}/questions/{question}', [AssignmentController::class, 'deleteQuestion'])
        ->middleware('role:Admin|Instructor|Superadmin')
        ->name('assignments.questions.destroy');

    // =========================================================================
    // Prerequisite Checking Route (Requirement 2.1)
    // =========================================================================
    Route::get('assignments/{assignment}/check-prerequisites', [AssignmentController::class, 'checkPrerequisites'])
        ->name('assignments.check-prerequisites');

    // =========================================================================
    // Override Routes (Requirements 24.1-24.4)
    // =========================================================================
    Route::get('assignments/{assignment}/overrides', [AssignmentController::class, 'listOverrides'])
        ->middleware('role:Admin|Instructor|Superadmin')
        ->name('assignments.overrides.index');
    Route::post('assignments/{assignment}/overrides', [AssignmentController::class, 'grantOverride'])
        ->middleware('role:Admin|Instructor|Superadmin')
        ->name('assignments.overrides.store');

    // =========================================================================
    // Assignment Duplication Route (Requirement 25.1)
    // =========================================================================
    Route::post('assignments/{assignment}/duplicate', [AssignmentController::class, 'duplicate'])
        ->middleware('role:Admin|Instructor|Superadmin')
        ->name('assignments.duplicate');

    // =========================================================================
    // Submission Routes
    // =========================================================================
    Route::get('assignments/{assignment}/submissions', [SubmissionController::class, 'index'])
        ->name('assignments.submissions.index');
    Route::post('assignments/{assignment}/submissions', [SubmissionController::class, 'store'])
        ->name('assignments.submissions.store');

    // New submission workflow endpoints (Requirements 6.3, 6.4, 7.3, 7.4, 8.3)
    Route::post('assignments/{assignment}/submissions/start', [SubmissionController::class, 'start'])
        ->name('assignments.submissions.start');

    // Deadline checking endpoint (Requirements 6.3, 6.4, 6.5)
    Route::get('assignments/{assignment}/check-deadline', [SubmissionController::class, 'checkDeadline'])
        ->name('assignments.check-deadline');

    // Attempt limit checking endpoint (Requirements 7.3, 7.4, 7.5, 7.6)
    Route::get('assignments/{assignment}/check-attempts', [SubmissionController::class, 'checkAttempts'])
        ->name('assignments.check-attempts');

    // Student's own submissions with highest marked (Requirement 22.3)
    Route::get('assignments/{assignment}/my-submissions', [SubmissionController::class, 'mySubmissions'])
        ->name('assignments.my-submissions');

    // Highest scoring submission (Requirements 8.4, 22.1, 22.2)
    Route::get('assignments/{assignment}/highest-submission', [SubmissionController::class, 'highestSubmission'])
        ->name('assignments.highest-submission');

    // Submission search endpoint (Requirements 27.1-27.6)
    Route::get('submissions/search', [SubmissionController::class, 'search'])
        ->middleware('role:Admin|Instructor|Superadmin')
        ->name('submissions.search');

    Route::get('submissions/{submission}', [SubmissionController::class, 'show'])
        ->name('submissions.show');
    Route::put('submissions/{submission}', [SubmissionController::class, 'update'])
        ->name('submissions.update');

    // Submit answers endpoint (Requirements 6.3, 6.4)
    Route::post('submissions/{submission}/submit', [SubmissionController::class, 'submit'])
        ->name('submissions.submit');

    Route::post('submissions/{submission}/grade', [SubmissionController::class, 'grade'])
        ->middleware('role:Admin|Instructor|Superadmin')
        ->name('submissions.grade');
});
