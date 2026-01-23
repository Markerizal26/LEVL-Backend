<?php

use Illuminate\Support\Facades\Route;
use Modules\Schemes\Http\Controllers\CourseController;
use Modules\Schemes\Http\Controllers\LessonBlockController;
use Modules\Schemes\Http\Controllers\LessonController;
use Modules\Schemes\Http\Controllers\ProgressController;
use Modules\Schemes\Http\Controllers\TagController;
use Modules\Schemes\Http\Controllers\UnitController;

Route::prefix('v1')->scopeBindings()->group(function () {

    // Public course routes
    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');

    Route::middleware(['auth:api', 'role:Superadmin|Admin|Instructor'])->group(function () {
        Route::post('courses', [CourseController::class, 'store'])
            ->middleware('can:create,Modules\\Schemes\\Models\\Course')
            ->name('courses.store');
        Route::put('courses/{course:slug}', [CourseController::class, 'update'])
            ->middleware('can:update,course')
            ->name('courses.update');
        Route::delete('courses/{course:slug}', [CourseController::class, 'destroy'])
            ->middleware('can:delete,course')
            ->name('courses.destroy');
        Route::put('courses/{course:slug}/publish', [CourseController::class, 'publish'])
            ->middleware('can:update,course')
            ->name('courses.publish');
        Route::put('courses/{course:slug}/unpublish', [CourseController::class, 'unpublish'])
            ->middleware('can:update,course')
            ->name('courses.unpublish');

        // Enrollment key management
        Route::post('courses/{course:slug}/enrollment-key/generate', [CourseController::class, 'generateEnrollmentKey'])
            ->name('courses.enrollment-key.generate');
        Route::put('courses/{course:slug}/enrollment-key', [CourseController::class, 'updateEnrollmentKey'])
            ->name('courses.enrollment-key.update');
        Route::delete('courses/{course:slug}/enrollment-key', [CourseController::class, 'removeEnrollmentKey'])
            ->name('courses.enrollment-key.destroy');
    });

    // Authenticated unit routes
    Route::middleware(['auth:api'])->group(function () {
        Route::get('courses/{course:slug}/units', [UnitController::class, 'index'])->name('courses.units.index');
        Route::get('courses/{course:slug}/units/{unit:slug}', [UnitController::class, 'show'])
            ->middleware('can:view,unit')
            ->name('courses.units.show');
    });

    // Unit management routes (Admin, Instructor)
    Route::middleware(['auth:api', 'role:Superadmin|Admin|Instructor'])->group(function () {
        Route::post('courses/{course:slug}/units', [UnitController::class, 'store'])
            ->name('courses.units.store');
        Route::put('courses/{course:slug}/units/reorder', [UnitController::class, 'reorder'])
            ->name('courses.units.reorder');
        Route::put('courses/{course:slug}/units/{unit:slug}', [UnitController::class, 'update'])
            ->name('courses.units.update');
        Route::delete('courses/{course:slug}/units/{unit:slug}', [UnitController::class, 'destroy'])
            ->name('courses.units.destroy');
        Route::put('courses/{course:slug}/units/{unit:slug}/publish', [UnitController::class, 'publish'])
            ->name('courses.units.publish');
        Route::put('courses/{course:slug}/units/{unit:slug}/unpublish', [UnitController::class, 'unpublish'])
            ->name('courses.units.unpublish');
    });

    // Authenticated lesson and progress routes
    Route::middleware(['auth:api'])->group(function () {
        Route::get('courses/{course:slug}/units/{unit:slug}/lessons', [LessonController::class, 'index'])
            ->name('courses.units.lessons.index');
        Route::get('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}', [LessonController::class, 'show'])
            ->middleware('can:view,lesson')
            ->name('courses.units.lessons.show');
        Route::get('courses/{course:slug}/progress', [ProgressController::class, 'show'])
            ->name('courses.progress.show');
        Route::post('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/complete', [ProgressController::class, 'completeLesson'])
            ->name('courses.units.lessons.complete');
        Route::post('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/uncomplete', [ProgressController::class, 'uncompleteLesson'])
            ->name('courses.units.lessons.uncomplete');
    });

    // Lesson management routes (Admin, Instructor)
    Route::middleware(['auth:api', 'role:Superadmin|Admin|Instructor'])->group(function () {
        Route::post('courses/{course:slug}/units/{unit:slug}/lessons', [LessonController::class, 'store'])
            ->name('courses.units.lessons.store');
        Route::put('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}', [LessonController::class, 'update'])
            ->name('courses.units.lessons.update');
        Route::delete('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}', [LessonController::class, 'destroy'])
            ->name('courses.units.lessons.destroy');
        Route::put('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/publish', [LessonController::class, 'publish'])
            ->name('courses.units.lessons.publish');
        Route::put('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/unpublish', [LessonController::class, 'unpublish'])
            ->name('courses.units.lessons.unpublish');
    });

    // Authenticated lesson block routes
    Route::middleware(['auth:api'])->group(function () {
        Route::get('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/blocks', [LessonBlockController::class, 'index'])
            ->name('courses.units.lessons.blocks.index');
        Route::get('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/blocks/{block:slug}', [LessonBlockController::class, 'show'])
            ->name('courses.units.lessons.blocks.show');
    });

    // Lesson block management routes (Admin, Instructor)
    Route::middleware(['auth:api', 'role:Superadmin|Admin|Instructor'])->group(function () {
        Route::post('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/blocks', [LessonBlockController::class, 'store'])
            ->name('courses.units.lessons.blocks.store');
        Route::put('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/blocks/{block:slug}', [LessonBlockController::class, 'update'])
            ->name('courses.units.lessons.blocks.update');
        Route::delete('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/blocks/{block:slug}', [LessonBlockController::class, 'destroy'])
            ->name('courses.units.lessons.blocks.destroy');
    });
});
