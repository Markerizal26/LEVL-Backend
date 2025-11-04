<?php

use Illuminate\Support\Facades\Route;
use Modules\Schemes\Http\Controllers\CourseController;
use Modules\Schemes\Http\Controllers\SchemesController;

Route::middleware(['auth:api'])->prefix('v1')->group(function () {
    Route::apiResource('schemes', SchemesController::class)->names('schemes');
    Route::apiResource('courses', CourseController::class)->names('courses');
});
