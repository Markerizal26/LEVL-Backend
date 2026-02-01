<?php

use Illuminate\Support\Facades\Route;
use Modules\Gamification\Http\Controllers\ChallengeController;
use Modules\Gamification\Http\Controllers\GamificationController;
use Modules\Gamification\Http\Controllers\LeaderboardController;

Route::middleware(['auth:api'])->prefix('v1')->group(function () {
    
    Route::get('challenges', [ChallengeController::class, 'index'])->name('challenges.index');
    Route::get('challenges/{challenge}', [ChallengeController::class, 'show'])->name('challenges.show');
    Route::post('challenges/{challenge}/claim', [ChallengeController::class, 'claim'])->name('challenges.claim');

    
    Route::get('leaderboards', [LeaderboardController::class, 'index'])->name('leaderboards.index');

    
    Route::prefix('user')->name('user.')->group(function () {
        
        Route::get('challenges', [ChallengeController::class, 'myChallenges'])->name('challenges.index'); 
        Route::get('challenges/completed', [ChallengeController::class, 'completed'])->name('challenges.completed');

        
        Route::get('rank', [LeaderboardController::class, 'myRank'])->name('gamification.rank');
        Route::get('gamification-summary', [GamificationController::class, 'summary'])->name('gamification.summary');
        
        
        Route::get('badges', [GamificationController::class, 'badges'])->name('gamification.badges');
        Route::get('points-history', [GamificationController::class, 'pointsHistory'])->name('gamification.points-history');
        Route::get('achievements', [GamificationController::class, 'achievements'])->name('gamification.achievements');
        Route::get('levels/{courseId}', [GamificationController::class, 'unitLevels'])->name('gamification.unit-levels');
    });
});
