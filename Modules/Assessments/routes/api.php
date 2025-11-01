<?php

use Illuminate\Support\Facades\Route;
use Modules\Assessments\Http\Controllers\AssessmentsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('assessments', AssessmentsController::class)->names('assessments');
});
