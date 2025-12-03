<?php

use Illuminate\Support\Facades\Route;
use Modules\Forums\Http\Controllers\ForumStatisticsController;
use Modules\Forums\Http\Controllers\ReactionController;
use Modules\Forums\Http\Controllers\ReplyController;
use Modules\Forums\Http\Controllers\ThreadController;
use Modules\Forums\Http\Middleware\CheckForumAccess;

Route::middleware(['auth:api'])->prefix('v1')->group(function () {

    // Thread routes for a specific scheme
    Route::prefix('schemes/{scheme}/forum')->middleware(CheckForumAccess::class)->as('schemes.forum.')->group(function () {
        // Thread listing and search
        Route::get('threads', [ThreadController::class, 'index'])->name('threads.index');
        Route::get('threads/search', [ThreadController::class, 'search'])->name('threads.search');

        // Thread CRUD
        Route::post('threads', [ThreadController::class, 'store'])->name('threads.store');
        Route::get('threads/{thread}', [ThreadController::class, 'show'])->name('threads.show');
        Route::put('threads/{thread}', [ThreadController::class, 'update'])->name('threads.update');
        Route::delete('threads/{thread}', [ThreadController::class, 'destroy'])->name('threads.destroy');

        // Thread moderation
        Route::post('threads/{thread}/pin', [ThreadController::class, 'pin'])->name('threads.pin');
        Route::post('threads/{thread}/close', [ThreadController::class, 'close'])->name('threads.close');
    });

    // Reply routes
    Route::prefix('forum')->as('forum.')->group(function () {
        // Reply CRUD
        Route::post('threads/{thread}/replies', [ReplyController::class, 'store'])->name('threads.replies.store');
        Route::put('replies/{reply}', [ReplyController::class, 'update'])->name('replies.update');
        Route::delete('replies/{reply}', [ReplyController::class, 'destroy'])->name('replies.destroy');

        // Reply moderation
        Route::post('replies/{reply}/accept', [ReplyController::class, 'accept'])->name('replies.accept');
    });

    // Reaction routes
    Route::prefix('forum')->as('forum.reactions.')->group(function () {
        Route::post('threads/{thread}/reactions', [ReactionController::class, 'toggleThreadReaction'])->name('threads.toggle');
        Route::post('replies/{reply}/reactions', [ReactionController::class, 'toggleReplyReaction'])->name('replies.toggle');
    });

    // Statistics routes
    Route::prefix('schemes/{scheme}/forum')->middleware(CheckForumAccess::class)->as('schemes.forum.statistics.')->group(function () {
        Route::get('statistics', [ForumStatisticsController::class, 'index'])->name('index');
        Route::get('statistics/me', [ForumStatisticsController::class, 'userStats'])->name('me');
    });
});
