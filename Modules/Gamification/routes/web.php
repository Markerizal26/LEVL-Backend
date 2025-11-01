<?php

use Illuminate\Support\Facades\Route;
use Modules\Gamification\Http\Controllers\GamificationController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('gamifications', GamificationController::class)->names('gamification');
});
