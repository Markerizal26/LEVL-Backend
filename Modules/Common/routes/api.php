<?php

use Illuminate\Support\Facades\Route;
use Modules\Common\Http\Controllers\CategoriesController;

Route::prefix('v1')->group(function () {

    Route::get('categories', [CategoriesController::class, 'index']);
    Route::get('categories/{category}', [CategoriesController::class, 'show']);

    Route::middleware(['auth:api', 'role:superadmin'])->group(function () {
        Route::post('categories', [CategoriesController::class, 'store']);
        Route::put('categories/{category}', [CategoriesController::class, 'update']);
        Route::delete('categories/{category}', [CategoriesController::class, 'destroy']);
    });
});
