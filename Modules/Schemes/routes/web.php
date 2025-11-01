<?php

use Illuminate\Support\Facades\Route;
use Modules\Schemes\Http\Controllers\SchemesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('schemes', SchemesController::class)->names('schemes');
});
