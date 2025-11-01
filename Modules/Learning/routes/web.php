<?php

use Illuminate\Support\Facades\Route;
use Modules\Learning\Http\Controllers\LearningController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('learnings', LearningController::class)->names('learning');
});
