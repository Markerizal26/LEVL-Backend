<?php

use Illuminate\Support\Facades\Route;
use Modules\Schemes\Http\Controllers\CourseController;
use Modules\Schemes\Http\Controllers\LessonBlockController;
use Modules\Schemes\Http\Controllers\LessonController;
use Modules\Schemes\Http\Controllers\UnitController;

Route::prefix('v1')->scopeBindings()->group(function () {

    Route::get('courses', [CourseController::class, 'index']);
    Route::get('courses/{course:slug}', [CourseController::class, 'show']);

    Route::middleware(['auth:api', 'role:super-admin|admin'])->group(function () {
        Route::post('courses', [CourseController::class, 'store'])->middleware('can:create,Modules\\Schemes\\Models\\Course');
        Route::put('courses/{course:slug}', [CourseController::class, 'update'])->middleware('can:update,course');
        Route::delete('courses/{course:slug}', [CourseController::class, 'destroy'])->middleware('can:delete,course');
        Route::put('courses/{course:slug}/publish', [CourseController::class, 'publish']);
        Route::put('courses/{course:slug}/unpublish', [CourseController::class, 'unpublish']);
    });

    Route::get('courses/{course:slug}/units', [UnitController::class, 'index']);
    Route::get('courses/{course:slug}/units/{unit:slug}', [UnitController::class, 'show']);

    Route::middleware(['auth:api', 'role:super-admin|admin'])->group(function () {
        Route::post('courses/{course:slug}/units', [UnitController::class, 'store']);
        Route::put('courses/{course:slug}/units/reorder', [UnitController::class, 'reorder']);
        Route::put('courses/{course:slug}/units/{unit:slug}', [UnitController::class, 'update']);
        Route::delete('courses/{course:slug}/units/{unit:slug}', [UnitController::class, 'destroy']);
        Route::put('courses/{course:slug}/units/{unit:slug}/publish', [UnitController::class, 'publish']);
        Route::put('courses/{course:slug}/units/{unit:slug}/unpublish', [UnitController::class, 'unpublish']);
    });

    Route::middleware(['auth:api'])->group(function () {
        Route::get('courses/{course:slug}/units/{unit:slug}/lessons', [LessonController::class, 'index']);
        Route::get('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}', [LessonController::class, 'show']);
    });

    Route::middleware(['auth:api', 'role:super-admin|admin'])->group(function () {
        Route::post('courses/{course:slug}/units/{unit:slug}/lessons', [LessonController::class, 'store']);
        Route::put('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}', [LessonController::class, 'update']);
        Route::delete('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}', [LessonController::class, 'destroy']);
        Route::put('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/publish', [LessonController::class, 'publish']);
        Route::put('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/unpublish', [LessonController::class, 'unpublish']);
    });

    Route::middleware(['auth:api'])->group(function () {
        Route::get('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/blocks', [LessonBlockController::class, 'index']);
        Route::get('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/blocks/{block:slug}', [LessonBlockController::class, 'show']);
    });
    Route::middleware(['auth:api', 'role:super-admin|admin'])->group(function () {
        Route::post('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/blocks', [LessonBlockController::class, 'store']);
        Route::put('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/blocks/{block:slug}', [LessonBlockController::class, 'update']);
        Route::delete('courses/{course:slug}/units/{unit:slug}/lessons/{lesson:slug}/blocks/{block:slug}', [LessonBlockController::class, 'destroy']);
    });
});
