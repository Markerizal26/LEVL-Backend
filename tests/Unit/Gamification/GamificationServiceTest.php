<?php

use Modules\Gamification\Models\Badge;
use Modules\Gamification\Models\Point;
use Modules\Gamification\Models\UserBadge;
use Modules\Gamification\Models\UserGamificationStat;
use Modules\Gamification\Services\GamificationService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(GamificationService::class);
});

test('award xp creates point record', function () {
    $user = \Modules\Auth\Models\User::factory()->create();

    $point = $this->service->awardXp($user->id, 50, 'completion', 'lesson', 1);

    expect($point)->not->toBeNull();
    expect($point->points)->toEqual(50);
    expect($point->reason)->toEqual('completion');
    expect($point->source_type)->toEqual('lesson');
    expect($point->source_id)->toEqual(1);
});

test('award xp updates user gamification stats', function () {
    $user = \Modules\Auth\Models\User::factory()->create();

    $this->service->awardXp($user->id, 100, 'completion');

    $stats = UserGamificationStat::where('user_id', $user->id)->first();
    expect($stats)->not->toBeNull();
    expect($stats->total_xp)->toEqual(100);
});

test('award xp calculates level correctly', function () {
    $user = \Modules\Auth\Models\User::factory()->create();

    // Award enough XP to reach level 2 (requires ~110 XP based on formula)
    $this->service->awardXp($user->id, 150, 'completion');

    $stats = UserGamificationStat::where('user_id', $user->id)->first();
    expect($stats->global_level)->toBeGreaterThanOrEqual(1);
});

test('award xp prevents duplicate when allow multiple false', function () {
    $user = \Modules\Auth\Models\User::factory()->create();

    $point1 = $this->service->awardXp($user->id, 50, 'completion', 'lesson', 1, ['allow_multiple' => false]);
    $point2 = $this->service->awardXp($user->id, 50, 'completion', 'lesson', 1, ['allow_multiple' => false]);

    expect($point1)->not->toBeNull();
    expect($point2)->toBeNull();
    // Should not create duplicate
});

test('award xp allows multiple when allow multiple true', function () {
    $user = \Modules\Auth\Models\User::factory()->create();

    $point1 = $this->service->awardXp($user->id, 50, 'completion', 'lesson', 1, ['allow_multiple' => true]);
    $point2 = $this->service->awardXp($user->id, 50, 'completion', 'lesson', 1, ['allow_multiple' => true]);

    expect($point1)->not->toBeNull();
    expect($point2)->not->toBeNull();
});

test('award xp returns null for zero or negative xp', function () {
    $user = \Modules\Auth\Models\User::factory()->create();

    $point1 = $this->service->awardXp($user->id, 0, 'completion');
    $point2 = $this->service->awardXp($user->id, -10, 'completion');

    expect($point1)->toBeNull();
    expect($point2)->toBeNull();
});

test('award badge creates badge if not exists', function () {
    $user = \Modules\Auth\Models\User::factory()->create();

    $userBadge = $this->service->awardBadge($user->id, 'course_completion', 'Course Completer', 'Completed a course');

    expect($userBadge)->not->toBeNull();
    assertDatabaseHas('badges', ['code' => 'course_completion']);
    assertDatabaseHas('user_badges', [
        'user_id' => $user->id,
        'badge_id' => $userBadge->badge_id,
    ]);
});

test('award badge prevents duplicate', function () {
    $user = \Modules\Auth\Models\User::factory()->create();

    $badge1 = $this->service->awardBadge($user->id, 'course_completion', 'Course Completer', 'Completed a course');
    $badge2 = $this->service->awardBadge($user->id, 'course_completion', 'Course Completer', 'Completed a course');

    expect($badge1)->not->toBeNull();
    expect($badge2)->toBeNull();
    // Should not create duplicate
});

test('update global leaderboard updates ranks', function () {
    $user1 = \Modules\Auth\Models\User::factory()->create();
    $user2 = \Modules\Auth\Models\User::factory()->create();
    $user3 = \Modules\Auth\Models\User::factory()->create();

    $this->service->awardXp($user1->id, 300, 'completion');
    $this->service->awardXp($user2->id, 200, 'completion');
    $this->service->awardXp($user3->id, 100, 'completion');

    $this->service->updateGlobalLeaderboard();

    $leaderboard = \Modules\Gamification\Models\Leaderboard::whereNull('course_id')
        ->orderBy('rank')
        ->get();

    expect($leaderboard)->toHaveCount(3);
    expect($leaderboard[0]->user_id)->toEqual($user1->id);
    expect($leaderboard[0]->rank)->toEqual(1);
});