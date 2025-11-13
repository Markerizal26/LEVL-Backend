<?php

use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Models\LessonProgress;
use Modules\Enrollments\Models\UnitProgress;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Services\ProgressionService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ProgressionService();
});

test('get enrollment for course returns enrollment when exists', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    $result = $this->service->getEnrollmentForCourse($course->id, $user->id);

    expect($result)->not->toBeNull();
    expect($result->id)->toEqual($enrollment->id);
});

test('get enrollment for course returns null when not exists', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();

    $result = $this->service->getEnrollmentForCourse($course->id, $user->id);

    expect($result)->toBeNull();
});

test('get enrollment for course only returns active or completed', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'cancelled',
    ]);

    $result = $this->service->getEnrollmentForCourse($course->id, $user->id);

    expect($result)->toBeNull();
});

test('can access lesson returns true for free mode', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create(['progression_mode' => 'free']);
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    $result = $this->service->canAccessLesson($lesson, $enrollment);

    expect($result)->toBeTrue();
});

test('can access lesson returns false when previous lesson not completed in sequential mode', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create(['progression_mode' => 'sequential']);
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson1 = Lesson::factory()->create(['unit_id' => $unit->id, 'order' => 1, 'status' => 'published']);
    $lesson2 = Lesson::factory()->create(['unit_id' => $unit->id, 'order' => 2, 'status' => 'published']);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    $result = $this->service->canAccessLesson($lesson2, $enrollment);

    expect($result)->toBeFalse();
});

test('can access lesson returns true when previous lesson completed in sequential mode', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create(['progression_mode' => 'sequential']);
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson1 = Lesson::factory()->create(['unit_id' => $unit->id, 'order' => 1, 'status' => 'published']);
    $lesson2 = Lesson::factory()->create(['unit_id' => $unit->id, 'order' => 2, 'status' => 'published']);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    LessonProgress::create([
        'enrollment_id' => $enrollment->id,
        'lesson_id' => $lesson1->id,
        'status' => 'completed',
        'progress_percent' => 100,
    ]);

    $result = $this->service->canAccessLesson($lesson2, $enrollment);

    expect($result)->toBeTrue();
});

test('mark lesson completed updates lesson progress', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id, 'status' => 'published']);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    $this->service->markLessonCompleted($lesson, $enrollment);

    $progress = LessonProgress::where('enrollment_id', $enrollment->id)
        ->where('lesson_id', $lesson->id)
        ->first();

    expect($progress)->not->toBeNull();
    expect($progress->status)->toEqual('completed');
    expect($progress->progress_percent)->toEqual(100);
});

test('mark lesson completed updates unit progress when all lessons complete', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson1 = Lesson::factory()->create(['unit_id' => $unit->id, 'order' => 1, 'status' => 'published']);
    $lesson2 = Lesson::factory()->create(['unit_id' => $unit->id, 'order' => 2, 'status' => 'published']);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    LessonProgress::create([
        'enrollment_id' => $enrollment->id,
        'lesson_id' => $lesson1->id,
        'status' => 'completed',
        'progress_percent' => 100,
    ]);

    $this->service->markLessonCompleted($lesson2, $enrollment);

    $unitProgress = UnitProgress::where('enrollment_id', $enrollment->id)
        ->where('unit_id', $unit->id)
        ->first();

    expect($unitProgress)->not->toBeNull();
    expect($unitProgress->status)->toEqual('completed');
});