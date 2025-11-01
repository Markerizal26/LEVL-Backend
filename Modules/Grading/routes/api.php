<?php

use Illuminate\Support\Facades\Route;
use Modules\Grading\Http\Controllers\GradingController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('gradings', GradingController::class)->names('grading');
});
