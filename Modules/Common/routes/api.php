<?php

use Illuminate\Support\Facades\Route;
use Modules\Common\Http\Controllers\CategoriesController;

Route::prefix('v1')->group(function () {

    // Public category routes
    Route::get('categories', [CategoriesController::class, 'index'])->name('categories.index');
    Route::get('categories/{category}', [CategoriesController::class, 'show'])->name('categories.show');

    // Admin category management routes
    Route::middleware(['auth:api', 'role:Superadmin'])->group(function () {
        Route::post('categories', [CategoriesController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [CategoriesController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoriesController::class, 'destroy'])->name('categories.destroy');
    });
});
