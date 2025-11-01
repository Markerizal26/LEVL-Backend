<?php

use Illuminate\Support\Facades\Route;
use Modules\Assessments\Http\Controllers\AssessmentsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('assessments', AssessmentsController::class)->names('assessments');
});
