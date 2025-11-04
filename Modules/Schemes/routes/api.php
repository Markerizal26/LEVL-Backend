<?php

use Illuminate\Support\Facades\Route;
use Modules\Schemes\Http\Controllers\CourseController;

Route::prefix('v1')->group(function () {

    Route::get('courses', [CourseController::class, 'index']);
    Route::get('courses/{course}', [CourseController::class, 'show']);

    Route::middleware(['auth:api', 'role:super-admin|admin'])->group(function () {
        Route::post('courses', [CourseController::class, 'store'])->middleware('can:create,Modules\\Schemes\\Models\\Course');
    });

    Route::middleware(['auth:api', 'role:super-admin|admin'])->group(function () {
        Route::put('courses/{course}', [CourseController::class, 'update'])->middleware('can:update,course');
        Route::delete('courses/{course}', [CourseController::class, 'destroy'])->middleware('can:delete,course');
    });
});
