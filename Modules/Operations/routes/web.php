<?php

use Illuminate\Support\Facades\Route;
use Modules\Operations\Http\Controllers\OperationsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('operations', OperationsController::class)->names('operations');
});
