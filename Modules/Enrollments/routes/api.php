<?php

use Illuminate\Support\Facades\Route;
use Modules\Enrollments\Http\Controllers\EnrollmentsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('enrollments', EnrollmentsController::class)->names('enrollments');
});
