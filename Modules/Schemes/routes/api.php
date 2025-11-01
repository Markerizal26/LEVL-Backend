<?php

use Illuminate\Support\Facades\Route;
use Modules\Schemes\Http\Controllers\SchemesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('schemes', SchemesController::class)->names('schemes');
});
