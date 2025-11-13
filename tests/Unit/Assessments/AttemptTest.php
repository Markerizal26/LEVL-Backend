<?php

use Modules\Assessments\Models\Attempt;
use Modules\Assessments\Models\Exercise;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('attempt can be created', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = \Modules\Schemes\Models\Course::factory()->create();
    $exercise = Exercise::create([
        'scope_type' => 'course',
        'scope_id' => $course->id,
        'created_by' => $user->id,
        'title' => 'Test Exercise',
    ]);

    $attempt = Attempt::create([
        'exercise_id' => $exercise->id,
        'user_id' => $user->id,
        'status' => 'in_progress',
        'total_questions' => 10,
    ]);

    assertDatabaseHas('attempts', [
        'id' => $attempt->id,
        'exercise_id' => $exercise->id,
        'user_id' => $user->id,
        'status' => 'in_progress',
    ]);
});

test('attempt belongs to exercise', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = \Modules\Schemes\Models\Course::factory()->create();
    $exercise = Exercise::create([
        'scope_type' => 'course',
        'scope_id' => $course->id,
        'created_by' => $user->id,
        'title' => 'Test Exercise',
    ]);
    $attempt = Attempt::create([
        'exercise_id' => $exercise->id,
        'user_id' => $user->id,
        'status' => 'in_progress',
    ]);

    expect($attempt->exercise->id)->toEqual($exercise->id);
});

test('attempt belongs to user', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = \Modules\Schemes\Models\Course::factory()->create();
    $exercise = Exercise::create([
        'scope_type' => 'course',
        'scope_id' => $course->id,
        'created_by' => $user->id,
        'title' => 'Test Exercise',
    ]);
    $attempt = Attempt::create([
        'exercise_id' => $exercise->id,
        'user_id' => $user->id,
        'status' => 'in_progress',
    ]);

    expect($attempt->user->id)->toEqual($user->id);
});