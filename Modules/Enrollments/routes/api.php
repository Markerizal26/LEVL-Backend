<?php

use Illuminate\Support\Facades\Route;
use Modules\Enrollments\Http\Controllers\EnrollmentsController;

Route::middleware(['auth:api'])->prefix('v1')->group(function () {
    Route::post('courses/{course:slug}/enrollments', [EnrollmentsController::class, 'enroll'])->name('courses.enrollments.enroll');
    Route::post('courses/{course:slug}/cancel', [EnrollmentsController::class, 'cancel'])->name('courses.enrollments.cancel');
    Route::post('courses/{course:slug}/withdraw', [EnrollmentsController::class, 'withdraw'])->name('courses.enrollments.withdraw');
    Route::post('enrollments/{enrollment}/approve', [EnrollmentsController::class, 'approve'])->name('enrollments.approve');
    Route::post('enrollments/{enrollment}/decline', [EnrollmentsController::class, 'decline'])->name('enrollments.decline');
    Route::post('enrollments/{enrollment}/remove', [EnrollmentsController::class, 'remove'])->name('enrollments.remove');
    Route::get('courses/enrollments', [EnrollmentsController::class, 'indexManaged'])->name('courses.enrollments.managed');
    Route::get('courses/{course:slug}/enrollment-status', [EnrollmentsController::class, 'status'])->name('courses.enrollments.status');
    Route::get('courses/{course:slug}/enrollments', [EnrollmentsController::class, 'indexByCourse'])->name('courses.enrollments.index');
    Route::get('enrollments', [EnrollmentsController::class, 'index'])->name('enrollments.index');
});
