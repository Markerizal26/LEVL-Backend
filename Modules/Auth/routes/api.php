<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AdminProfileController;
use Modules\Auth\Http\Controllers\AuthApiController;
use Modules\Auth\Http\Controllers\PasswordResetController;
use Modules\Auth\Http\Controllers\ProfileAccountController;
use Modules\Auth\Http\Controllers\ProfileAchievementController;
use Modules\Auth\Http\Controllers\ProfileActivityController;
use Modules\Auth\Http\Controllers\ProfileController;
use Modules\Auth\Http\Controllers\ProfilePasswordController;
use Modules\Auth\Http\Controllers\ProfilePrivacyController;
use Modules\Auth\Http\Controllers\ProfileStatisticsController;
use Modules\Auth\Http\Controllers\PublicProfileController;

Route::prefix("v1")
  ->as("auth.")
  ->group(function () {
    // Auth endpoints with rate limiting (10 requests per minute)
    Route::middleware(["throttle:auth"])->group(function () {
      Route::post("/auth/register", [AuthApiController::class, "register"])->name("register");
      Route::post("/auth/login", [AuthApiController::class, "login"])->name("login");
      Route::get("/auth/google/redirect", [AuthApiController::class, "googleRedirect"])->name(
        "google.redirect",
      );
      Route::get("/auth/google/callback", [AuthApiController::class, "googleCallback"])->name(
        "google.callback",
      );

      Route::post("/auth/email/verify", [AuthApiController::class, "verifyEmail"])->name(
        "email.verify",
      );
      Route::post("/auth/email/verify/by-token", [
        AuthApiController::class,
        "verifyEmailByToken",
      ])->name("email.verify.by-token");
    });

    Route::post("/auth/refresh", [AuthApiController::class, "refresh"])
      ->middleware([\Modules\Auth\Http\Middleware\AllowExpiredToken::class, "throttle:auth"])
      ->name("refresh");

    Route::middleware(["auth:api", "throttle:api"])->group(function () {
      Route::post("/auth/logout", [AuthApiController::class, "logout"])->name("logout");
      Route::get("/profile", [AuthApiController::class, "profile"])->name("profile");
      Route::put("/profile", [AuthApiController::class, "updateProfile"])->name("profile.update");
      Route::post("/auth/set-username", [AuthApiController::class, "setUsername"])->name(
        "set.username",
      );
      Route::post("/auth/email/verify/send", [
        AuthApiController::class,
        "sendEmailVerification",
      ])->name("email.verify.send");
      Route::post("/profile/email/verify", [AuthApiController::class, "verifyEmailChange"])->name(
        "email.change.verify",
      );
      Route::post("/profile/email/request", [AuthApiController::class, "requestEmailChange"])->name(
        "email.change.request",
      );

      // Profile Management Routes
      Route::prefix("profile")
        ->as("profile.")
        ->group(function () {
          Route::get("/", [ProfileController::class, "index"])->name("index");
          Route::put("/", [ProfileController::class, "update"])->name("update");
          Route::post("/avatar", [ProfileController::class, "uploadAvatar"])->name("avatar.upload");
          Route::delete("/avatar", [ProfileController::class, "deleteAvatar"])->name(
            "avatar.delete",
          );

          // Privacy Settings
          Route::get("/privacy", [ProfilePrivacyController::class, "index"])->name("privacy.index");
          Route::put("/privacy", [ProfilePrivacyController::class, "update"])->name(
            "privacy.update",
          );

          // Activity History
          Route::get("/activities", [ProfileActivityController::class, "index"])->name(
            "activities.index",
          );

          // Achievements
          Route::get("/achievements", [ProfileAchievementController::class, "index"])->name(
            "achievements.index",
          );
          Route::post("/badges/{badge}/pin", [
            ProfileAchievementController::class,
            "pinBadge",
          ])->name("badges.pin");
          Route::delete("/badges/{badge}/unpin", [
            ProfileAchievementController::class,
            "unpinBadge",
          ])->name("badges.unpin");

          // Statistics
          Route::get("/statistics", [ProfileStatisticsController::class, "index"])->name(
            "statistics.index",
          );

          // Password Management
          Route::put("/password", [ProfilePasswordController::class, "update"])->name(
            "password.update",
          );

          // Account Management
          Route::delete("/account", [ProfileAccountController::class, "destroy"])->name(
            "account.delete",
          );
          Route::post("/account/restore", [ProfileAccountController::class, "restore"])->name(
            "account.restore",
          );
        });

      // Public Profile
      Route::get("/users/{user}/profile", [PublicProfileController::class, "show"])->name(
        "users.profile.show",
      );

      // Admin Profile Management
      Route::prefix("admin/users/{user}")
        ->as("admin.users.")
        ->middleware("role:Admin")
        ->group(function () {
          Route::get("/profile", [AdminProfileController::class, "show"])->name("profile.show");
          Route::put("/profile", [AdminProfileController::class, "update"])->name("profile.update");
          Route::post("/suspend", [AdminProfileController::class, "suspend"])->name("suspend");
          Route::post("/activate", [AdminProfileController::class, "activate"])->name("activate");
          Route::get("/audit-logs", [AdminProfileController::class, "auditLogs"])->name(
            "audit-logs",
          );
        });

      Route::middleware(["role:Superadmin"])->group(function () {
        Route::post("/auth/instructor", [AuthApiController::class, "createInstructor"])->name(
          "instructor.create",
        );
        Route::post("/auth/admin", [AuthApiController::class, "createAdmin"])->name("admin.create");
        Route::post("/auth/super-admin", [AuthApiController::class, "createSuperAdmin"])->name(
          "super.create",
        );
        Route::post("/auth/credentials/resend", [
          AuthApiController::class,
          "resendCredentials",
        ])->name("credentials.resend");
        Route::put("/auth/users/{user}/status", [
          AuthApiController::class,
          "updateUserStatus",
        ])->name("users.status.update");
        Route::get("/auth/users/{user}", [AuthApiController::class, "showUser"])->name(
          "users.show",
        );
      });

      Route::middleware(["role:Superadmin|Admin"])->group(function () {
        Route::get("/auth/users", [AuthApiController::class, "listUsers"])->name("users.index");

        // Bulk operations (Superadmin & Admin only)
        Route::post("/users/bulk/export", [
          \Modules\Auth\Http\Controllers\UserBulkController::class,
          "export",
        ])->name("users.bulk.export");
        Route::post("/users/bulk/activate", [
          \Modules\Auth\Http\Controllers\UserBulkController::class,
          "activate",
        ])->name("users.bulk.activate");
        Route::post("/users/bulk/deactivate", [
          \Modules\Auth\Http\Controllers\UserBulkController::class,
          "deactivate",
        ])->name("users.bulk.deactivate");
      });

      // Bulk delete (Superadmin only)
      Route::middleware(["role:Superadmin"])->group(function () {
        Route::delete("/users/bulk/delete", [
          \Modules\Auth\Http\Controllers\UserBulkController::class,
          "delete",
        ])->name("users.bulk.delete");
      });
    });

    // Password reset endpoints with auth rate limiting
    Route::middleware(["throttle:auth"])->group(function () {
      Route::post("/auth/password/forgot", [PasswordResetController::class, "forgot"])->name(
        "password.forgot",
      );
      Route::post("/auth/password/forgot/confirm", [
        PasswordResetController::class,
        "confirmForgot",
      ])->name("password.forgot.confirm");
    });
    Route::middleware(["auth:api", "throttle:api"])
      ->post("/auth/password/reset", [PasswordResetController::class, "reset"])
      ->name("password.reset");
  });
