<?php

use App\Http\Controllers\Api\ActivityLogController;
use Illuminate\Support\Facades\Route;
use Modules\Common\Http\Controllers\AuditLogController;
use Modules\Common\Http\Controllers\BadgesController;
use Modules\Common\Http\Controllers\CategoriesController;
use Modules\Common\Http\Controllers\ChallengeManagementController;
use Modules\Common\Http\Controllers\LevelConfigsController;
use Modules\Common\Http\Controllers\MasterDataController;
use Modules\Schemes\Http\Controllers\TagController;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:api', 'role:Superadmin'])
        ->name('activity-logs.')
        ->group(function () {
            Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('index');
            Route::get('activity-logs/{id}', [ActivityLogController::class, 'show'])->name('show');
        });

    Route::middleware(['auth:api', 'role:Admin|Superadmin'])
        ->name('audit-logs.')
        ->group(function () {
            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('index');
            Route::get('audit-logs/{id}', [AuditLogController::class, 'show'])->name('show');
            Route::get('audit-logs/meta/actions', [AuditLogController::class, 'actions'])->name('actions');
        });

    Route::prefix('tags')->name('tags.')->group(function () {
        Route::get('/', [TagController::class, 'index'])->name('index');
        Route::get('/{tag:slug}', [TagController::class, 'show'])->name('show');
        
        // Allowed for Superadmin, Admin, and Instructor
        Route::middleware(['auth:api', 'role:Superadmin|Admin|Instructor'])->group(function () {
            Route::post('/', [TagController::class, 'store'])->name('store');
            Route::put('/{tag:slug}', [TagController::class, 'update'])->name('update');
            Route::delete('/{tag:slug}', [TagController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('master-data')->name('master-data.')->group(function () {
        // Types List
        Route::get('types', [MasterDataController::class, 'types'])->name('types.index');
        
        // Data Items (Paginated)
        Route::get('{type}', [MasterDataController::class, 'index'])->name('index');
        // Data Items (All - helpers)
        Route::get('{type}/all', [MasterDataController::class, 'get'])->name('all');
        
        // CRUD Items
        Route::get('{type}/{id}', [MasterDataController::class, 'show'])->name('show');
        
        Route::middleware(['auth:api', 'role:Superadmin'])->group(function () {
            Route::post('{type}', [MasterDataController::class, 'store'])->name('store');
            Route::put('{type}/{id}', [MasterDataController::class, 'update'])->name('update');
            Route::delete('{type}/{id}', [MasterDataController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoriesController::class, 'index'])->name('index');
        Route::get('/{category}', [CategoriesController::class, 'show'])->name('show');

        Route::middleware(['auth:api', 'role:Superadmin'])->group(function () {
            Route::post('/', [CategoriesController::class, 'store'])->name('store');
            Route::put('/{category}', [CategoriesController::class, 'update'])->name('update');
            Route::delete('/{category}', [CategoriesController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('badges')->name('badges.')->group(function () {
        Route::get('/', [BadgesController::class, 'index'])->name('index');
        Route::get('/{badge}', [BadgesController::class, 'show'])->name('show');

        Route::middleware(['auth:api', 'role:Superadmin'])->group(function () {
            Route::post('/', [BadgesController::class, 'store'])->name('store');
            Route::put('/{badge}', [BadgesController::class, 'update'])->name('update');
            Route::delete('/{badge}', [BadgesController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('level-configs')->name('level-configs.')->group(function () {
        Route::get('/', [LevelConfigsController::class, 'index'])->name('index');
        Route::get('/{level_config}', [LevelConfigsController::class, 'show'])->name('show');

        Route::middleware(['auth:api', 'role:Superadmin'])->group(function () {
            Route::post('/', [LevelConfigsController::class, 'store'])->name('store');
            Route::put('/{level_config}', [LevelConfigsController::class, 'update'])->name('update');
            Route::delete('/{level_config}', [LevelConfigsController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('management/challenges')->name('challenges.')->group(function () {
        Route::get('/', [ChallengeManagementController::class, 'index'])->name('index');
        Route::get('/{challenge}', [ChallengeManagementController::class, 'show'])->name('show');

        Route::middleware(['auth:api', 'role:Superadmin'])->group(function () {
            Route::post('/', [ChallengeManagementController::class, 'store'])->name('store');
            Route::put('/{challenge}', [ChallengeManagementController::class, 'update'])->name('update');
            Route::delete('/{challenge}', [ChallengeManagementController::class, 'destroy'])->name('destroy');
        });
    });
});