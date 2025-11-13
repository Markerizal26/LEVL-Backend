<?php

use Illuminate\Support\Facades\Event;
use Modules\Learning\Events\AssignmentPublished;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Services\AssignmentService;
use Modules\Schemes\Models\Lesson;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = new AssignmentService();
    Event::fake();
});

test('create creates assignment with defaults', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = \Modules\Schemes\Models\Course::factory()->create();
    $unit = \Modules\Schemes\Models\Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);

    $assignment = $this->service->create([
        'lesson_id' => $lesson->id,
        'title' => 'Test Assignment',
    ], $user->id);

    expect($assignment)->not->toBeNull();
    expect($assignment->title)->toEqual('Test Assignment');
    expect($assignment->submission_type)->toEqual('text');
    expect($assignment->max_score)->toEqual(100);
    expect($assignment->status)->toEqual('draft');
});

test('create uses provided values', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = \Modules\Schemes\Models\Course::factory()->create();
    $unit = \Modules\Schemes\Models\Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);

    $assignment = $this->service->create([
        'lesson_id' => $lesson->id,
        'title' => 'Test Assignment',
        'description' => 'Test Description',
        'submission_type' => 'file',
        'max_score' => 50,
        'status' => 'published',
        'allow_resubmit' => false,
        'late_penalty_percent' => 20,
    ], $user->id);

    expect($assignment->description)->toEqual('Test Description');
    expect($assignment->submission_type)->toEqual('file');
    expect($assignment->max_score)->toEqual(50);
    expect($assignment->status)->toEqual('published');
    expect($assignment->allow_resubmit)->toBeFalse();
    expect($assignment->late_penalty_percent)->toEqual(20);
});

test('update modifies assignment', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = \Modules\Schemes\Models\Course::factory()->create();
    $unit = \Modules\Schemes\Models\Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $assignment = Assignment::create([
        'lesson_id' => $lesson->id,
        'created_by' => $user->id,
        'title' => 'Original Title',
        'submission_type' => 'text',
    ]);

    $updated = $this->service->update($assignment, [
        'title' => 'Updated Title',
        'max_score' => 75,
    ]);

    expect($updated->title)->toEqual('Updated Title');
    expect($updated->max_score)->toEqual(75);
});

test('publish changes status to published', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = \Modules\Schemes\Models\Course::factory()->create();
    $unit = \Modules\Schemes\Models\Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $assignment = Assignment::create([
        'lesson_id' => $lesson->id,
        'created_by' => $user->id,
        'title' => 'Test Assignment',
        'submission_type' => 'text',
        'status' => 'draft',
    ]);

    $result = $this->service->publish($assignment);

    expect($result->status)->toEqual('published');
    Event::assertDispatched(AssignmentPublished::class);
});

test('publish does not dispatch event if already published', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = \Modules\Schemes\Models\Course::factory()->create();
    $unit = \Modules\Schemes\Models\Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $assignment = Assignment::create([
        'lesson_id' => $lesson->id,
        'created_by' => $user->id,
        'title' => 'Test Assignment',
        'submission_type' => 'text',
        'status' => 'published',
    ]);

    Event::fake();
    $this->service->publish($assignment);

    Event::assertNotDispatched(AssignmentPublished::class);
});

test('unpublish changes status to draft', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = \Modules\Schemes\Models\Course::factory()->create();
    $unit = \Modules\Schemes\Models\Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $assignment = Assignment::create([
        'lesson_id' => $lesson->id,
        'created_by' => $user->id,
        'title' => 'Test Assignment',
        'submission_type' => 'text',
        'status' => 'published',
    ]);

    $result = $this->service->unpublish($assignment);

    expect($result->status)->toEqual('draft');
});

test('delete removes assignment', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = \Modules\Schemes\Models\Course::factory()->create();
    $unit = \Modules\Schemes\Models\Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $assignment = Assignment::create([
        'lesson_id' => $lesson->id,
        'created_by' => $user->id,
        'title' => 'Test Assignment',
        'submission_type' => 'text',
    ]);

    $result = $this->service->delete($assignment);

    expect($result)->toBeTrue();
    assertDatabaseMissing('assignments', ['id' => $assignment->id]);
});