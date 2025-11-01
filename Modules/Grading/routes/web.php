<?php

use Illuminate\Support\Facades\Route;
use Modules\Grading\Http\Controllers\GradingController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('gradings', GradingController::class)->names('grading');
});
