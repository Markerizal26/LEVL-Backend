<?php

use Illuminate\Support\Facades\Route;
use Modules\Common\Http\Controllers\CommonController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('commons', CommonController::class)->names('common');
});
