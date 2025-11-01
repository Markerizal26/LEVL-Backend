<?php

use Illuminate\Support\Facades\Route;
use Modules\Operations\Http\Controllers\OperationsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('operations', OperationsController::class)->names('operations');
});
