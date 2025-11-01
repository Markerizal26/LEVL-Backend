<?php

use Illuminate\Support\Facades\Route;
use Modules\Enrollments\Http\Controllers\EnrollmentsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('enrollments', EnrollmentsController::class)->names('enrollments');
});
