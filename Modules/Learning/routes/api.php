<?php

use Illuminate\Support\Facades\Route;
use Modules\Learning\Http\Controllers\LearningController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('learnings', LearningController::class)->names('learning');
});
