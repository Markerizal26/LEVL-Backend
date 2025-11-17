<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthApiController;
use Modules\Auth\Http\Controllers\PasswordResetController;

Route::prefix('v1')->as('auth.')->group(function () {
    Route::post('/auth/register', [AuthApiController::class, 'register'])->name('register');
    Route::post('/auth/login', [AuthApiController::class, 'login'])->name('login');
    Route::get('/auth/google/redirect', [AuthApiController::class, 'googleRedirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [AuthApiController::class, 'googleCallback'])->name('google.callback');

    Route::post('/auth/email/verify', [AuthApiController::class, 'verifyEmail'])->name('email.verify');
    Route::post('/auth/email/verify/by-token', [AuthApiController::class, 'verifyEmailByToken'])->name('email.verify.by-token');

    Route::post('/auth/refresh', [AuthApiController::class, 'refresh'])
        ->middleware([\Modules\Auth\Http\Middleware\AllowExpiredToken::class])
        ->name('refresh');

    Route::middleware(['auth:api'])->group(function () {
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('logout');
        Route::get('/profile', [AuthApiController::class, 'profile'])->name('profile');
        Route::put('/profile', [AuthApiController::class, 'updateProfile'])->name('profile.update');
        Route::post('/auth/set-username', [AuthApiController::class, 'setUsername'])->name('set.username');
        Route::post('/auth/email/verify/send', [AuthApiController::class, 'sendEmailVerification'])->name('email.verify.send');
        Route::post('/profile/email/verify', [AuthApiController::class, 'verifyEmailChange'])->name('email.change.verify');
        Route::post('/profile/email/request', [AuthApiController::class, 'requestEmailChange'])->name('email.change.request');

        Route::middleware(['role:Superadmin'])->group(function () {
            Route::post('/auth/instructor', [AuthApiController::class, 'createInstructor'])->name('instructor.create');
            Route::post('/auth/admin', [AuthApiController::class, 'createAdmin'])->name('admin.create');
            Route::post('/auth/super-admin', [AuthApiController::class, 'createSuperAdmin'])->name('super.create');
            Route::post('/auth/credentials/resend', [AuthApiController::class, 'resendCredentials'])->name('credentials.resend');
            Route::put('/auth/users/{user}/status', [AuthApiController::class, 'updateUserStatus'])->name('users.status.update');
            Route::get('/auth/users', [AuthApiController::class, 'listUsers'])->name('users.index');
            Route::get('/auth/users/{user}', [AuthApiController::class, 'showUser'])->name('users.show');
        });
    });

    
    
    Route::post('/auth/password/forgot', [PasswordResetController::class, 'forgot'])->name('password.forgot');
    Route::post('/auth/password/forgot/confirm', [PasswordResetController::class, 'confirmForgot'])->name('password.forgot.confirm');
    Route::middleware(['auth:api'])->post('/auth/password/reset', [PasswordResetController::class, 'reset'])->name('password.reset');
});
