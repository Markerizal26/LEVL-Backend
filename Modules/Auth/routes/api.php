<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthApiController;
use Modules\Auth\Http\Controllers\PasswordResetController;

Route::prefix('v1')->as('auth.')->group(function () {
    Route::post('/auth/register', [AuthApiController::class, 'register'])->name('register');
    Route::post('/auth/login', [AuthApiController::class, 'login'])->name('login');

    Route::middleware(['auth:api'])->group(function () {
        Route::post('/auth/refresh', [AuthApiController::class, 'refresh'])->name('refresh');
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('logout');
        Route::get('/profile', [AuthApiController::class, 'profile'])->name('profile');
        Route::put('/profile', [AuthApiController::class, 'updateProfile'])->name('profile.update');
        Route::post('/auth/email/verify', [AuthApiController::class, 'sendEmailVerification'])->name('email.verify.send');
        Route::post('/profile/email/request', [AuthApiController::class, 'requestEmailChange'])->name('email.change.request');
    });

    Route::get('/auth/email/verify', [AuthApiController::class, 'verifyEmail'])->name('email.verify');
    Route::post('/profile/email/verify', [AuthApiController::class, 'verifyEmailChange'])->name('email.change.verify');

    Route::post('/auth/password/forgot', [PasswordResetController::class, 'forgot'])->name('password.forgot');
    Route::post('/auth/password/forgot/confirm', [PasswordResetController::class, 'confirmForgot'])->name('password.forgot.confirm');
    Route::middleware(['auth:api'])->post('/auth/password/reset', [PasswordResetController::class, 'reset'])->name('password.reset');
});
