<?php

use App\Http\Controllers\Api\ActivityLogController;
use Illuminate\Support\Facades\Route;
use Modules\Common\Http\Controllers\AuditLogController;
use Modules\Common\Http\Controllers\MasterDataController;
use Modules\Schemes\Http\Controllers\TagController;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:api', 'role:Superadmin'])
        ->prefix('activity-logs')
        ->name('activity-logs.')
        ->group(function () {
            Route::get('/', [ActivityLogController::class, 'index'])->name('index');
            Route::get('/{id}', [ActivityLogController::class, 'show'])->name('show');
        });

    // Audit Log Routes (Requirement 20.7)
    // Restricted to Admin and Superadmin roles
    Route::middleware(['auth:api', 'role:Admin|Superadmin'])
        ->prefix('audit-logs')
        ->name('audit-logs.')
        ->group(function () {
            Route::get('/actions', [AuditLogController::class, 'actions'])->name('actions');
            Route::get('/', [AuditLogController::class, 'index'])->name('index');
            Route::get('/{id}', [AuditLogController::class, 'show'])->name('show');
        });

    Route::prefix('master-data')
        ->name('master-data.')
        ->group(function () {
            // Public Routes
            Route::get('/', [MasterDataController::class, 'types'])->name('index');
            Route::get('tags', [TagController::class, 'index'])->name('tags.index');
            Route::get('tags/{tag:slug}', [TagController::class, 'show'])->name('tags.show');
            Route::get('{type}/items', [MasterDataController::class, 'index'])->name('items.index');
            Route::get('{type}/items/{id}', [MasterDataController::class, 'show'])->name('items.show');

            // Superadmin Routes
            Route::middleware(['auth:api', 'role:Superadmin'])->group(function () {
                Route::post('tags', [TagController::class, 'store'])->name('tags.store');
                Route::put('tags/{tag:slug}', [TagController::class, 'update'])->name('tags.update');
                Route::delete('tags/{tag:slug}', [TagController::class, 'destroy'])->name('tags.destroy');

                Route::post('{type}/items', [MasterDataController::class, 'store'])->name('items.store');
                Route::put('{type}/items/{id}', [MasterDataController::class, 'update'])->name(
                    'items.update',
                );
                Route::delete('{type}/items/{id}', [MasterDataController::class, 'destroy'])->name(
                    'items.destroy',
                );
            });

            // Authenticated Routes (Dynamic Master Data)
            Route::middleware(['auth:api'])->group(function () {
                Route::get('{type}', [MasterDataController::class, 'get'])->name('get');
            });
        });
});
