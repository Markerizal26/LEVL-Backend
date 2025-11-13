<?php

use Modules\Grading\Models\Grade;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('grade can be created', function () {
    $user = \Modules\Auth\Models\User::factory()->create();

    $grade = Grade::create([
        'user_id' => $user->id,
        'source_type' => 'assignment',
        'source_id' => 1,
        'score' => 85,
        'max_score' => 100,
    ]);

    assertDatabaseHas('grades', [
        'id' => $grade->id,
        'user_id' => $user->id,
        'score' => 85,
    ]);
});