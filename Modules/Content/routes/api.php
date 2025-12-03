<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\Http\Controllers\AnnouncementController;
use Modules\Content\Http\Controllers\ContentApprovalController;
use Modules\Content\Http\Controllers\ContentStatisticsController;
use Modules\Content\Http\Controllers\CourseAnnouncementController;
use Modules\Content\Http\Controllers\NewsController;
use Modules\Content\Http\Controllers\SearchController;

Route::middleware(['auth:api'])->prefix('v1')->group(function () {
    // Announcements
    Route::prefix('announcements')->as('announcements.')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])->name('index');
        Route::post('/', [AnnouncementController::class, 'store'])->name('store');
        Route::get('/{announcement}', [AnnouncementController::class, 'show'])->name('show');
        Route::put('/{announcement}', [AnnouncementController::class, 'update'])->name('update');
        Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])->name('destroy');
        Route::post('/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('publish');
        Route::post('/{announcement}/schedule', [AnnouncementController::class, 'schedule'])->name('schedule');
        Route::post('/{announcement}/read', [AnnouncementController::class, 'markAsRead'])->name('read');
    });

    // News
    Route::prefix('news')->as('news.')->group(function () {
        Route::get('/', [NewsController::class, 'index'])->name('index');
        Route::post('/', [NewsController::class, 'store'])->name('store');
        Route::get('/trending', [NewsController::class, 'trending'])->name('trending');
        Route::get('/{news:slug}', [NewsController::class, 'show'])->name('show');
        Route::put('/{news:slug}', [NewsController::class, 'update'])->name('update');
        Route::delete('/{news:slug}', [NewsController::class, 'destroy'])->name('destroy');
        Route::post('/{news:slug}/publish', [NewsController::class, 'publish'])->name('publish');
        Route::post('/{news:slug}/schedule', [NewsController::class, 'schedule'])->name('schedule');
    });

    // Course Announcements
    Route::prefix('courses/{course}/announcements')->as('courses.announcements.')->group(function () {
        Route::get('/', [CourseAnnouncementController::class, 'index'])->name('index');
        Route::post('/', [CourseAnnouncementController::class, 'store'])->name('store');
    });

    // Statistics
    Route::prefix('content/statistics')->as('content.statistics.')->group(function () {
        Route::get('/', [ContentStatisticsController::class, 'index'])->name('index');
        Route::get('/announcements/{announcement}', [ContentStatisticsController::class, 'showAnnouncement'])->name('announcements.show');
        Route::get('/news/{news:slug}', [ContentStatisticsController::class, 'showNews'])->name('news.show');
        Route::get('/trending', [ContentStatisticsController::class, 'trending'])->name('trending');
        Route::get('/most-viewed', [ContentStatisticsController::class, 'mostViewed'])->name('most-viewed');
    });

    // Search
    Route::get('/content/search', [SearchController::class, 'search'])->name('content.search');

    // Content Approval Workflow
    Route::prefix('content')->as('content.approval.')->group(function () {
        Route::post('/{type}/{id}/submit', [ContentApprovalController::class, 'submit'])->name('submit');
        Route::post('/{type}/{id}/approve', [ContentApprovalController::class, 'approve'])->name('approve');
        Route::post('/{type}/{id}/reject', [ContentApprovalController::class, 'reject'])->name('reject');
        Route::get('/pending-review', [ContentApprovalController::class, 'pendingReview'])->name('pending-review');
    });
});
