<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Modules\Common\Models\SystemSetting;
use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Models\LessonProgress;
use Modules\Learning\Events\SubmissionCreated;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;
use Modules\Learning\Services\SubmissionService;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = new SubmissionService();
    Event::fake();
    SystemSetting::set('learning.allow_resubmit', true);
    SystemSetting::set('learning.late_penalty_percent', 10);
});

test('create submission creates submission with correct status', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);
    $assignment = Assignment::create([
        'lesson_id' => $lesson->id,
        'created_by' => $user->id,
        'title' => 'Test Assignment',
        'submission_type' => 'text',
        'status' => 'published',
        'deadline_at' => now()->addDays(7),
    ]);

    $submission = $this->service->create($assignment, $user->id, [
        'answer_text' => 'Test answer',
    ]);

    expect($submission)->not->toBeNull();
    expect($submission->status)->toEqual('submitted');
    expect($submission->attempt_number)->toEqual(1);
    expect($submission->is_late)->toBeFalse();
    expect($submission->is_resubmission)->toBeFalse();
});

test('create submission marks as late when past deadline', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);
    $assignment = Assignment::create([
        'lesson_id' => $lesson->id,
        'created_by' => $user->id,
        'title' => 'Test Assignment',
        'status' => 'published',
        'deadline_at' => now()->subDays(1),
    ]);

    $submission = $this->service->create($assignment, $user->id, [
        'answer_text' => 'Late submission',
    ]);

    expect($submission->status)->toEqual('late');
    expect($submission->is_late)->toBeTrue();
});

test('create submission increments attempt count', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);
    $assignment = Assignment::create([
        'lesson_id' => $lesson->id,
        'created_by' => $user->id,
        'title' => 'Test Assignment',
        'submission_type' => 'text',
        'status' => 'published',
    ]);

    $this->service->create($assignment, $user->id, ['answer_text' => 'First']);
    $submission2 = $this->service->create($assignment, $user->id, ['answer_text' => 'Second']);

    expect($submission2->attempt_number)->toEqual(2);
    expect($submission2->is_resubmission)->toBeTrue();

    $progress = LessonProgress::where('enrollment_id', $enrollment->id)
        ->where('lesson_id', $lesson->id)
        ->first();

    expect($progress->attempt_count)->toEqual(2);
});

test('create submission throws exception when resubmit not allowed', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);
    $assignment = Assignment::create([
        'lesson_id' => $lesson->id,
        'created_by' => $user->id,
        'title' => 'Test Assignment',
        'submission_type' => 'text',
        'status' => 'published',
        'allow_resubmit' => false,
    ]);

    $this->service->create($assignment, $user->id, ['answer_text' => 'First']);

    SystemSetting::set('learning.allow_resubmit', false);

    expect(fn () => $this->service->create($assignment, $user->id, ['answer_text' => 'Second']))
        ->toThrow(ValidationException::class);
});

test('create submission dispatches submission created event', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);
    $assignment = Assignment::create([
        'lesson_id' => $lesson->id,
        'created_by' => $user->id,
        'title' => 'Test Assignment',
        'submission_type' => 'text',
        'status' => 'published',
    ]);

    $this->service->create($assignment, $user->id, ['answer_text' => 'Test']);

    Event::assertDispatched(SubmissionCreated::class);
});

test('grade applies late penalty when submission is late', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);
    $assignment = Assignment::create([
        'lesson_id' => $lesson->id,
        'created_by' => $user->id,
        'title' => 'Test Assignment',
        'submission_type' => 'text',
        'status' => 'published',
        'late_penalty_percent' => 20,
    ]);
    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'user_id' => $user->id,
        'enrollment_id' => $enrollment->id,
        'is_late' => true,
        'status' => 'late',
    ]);

    $graded = $this->service->grade($submission, 100, 'Good work');

    expect($graded->status)->toEqual('graded');
    expect($graded->grade)->not->toBeNull();
    expect($graded->grade->score)->toEqual(80);
    expect($graded->grade->feedback)->toEqual('Good work');
});

test('grade uses assignment late penalty over system setting', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);
    $assignment = Assignment::create([
        'lesson_id' => $lesson->id,
        'created_by' => $user->id,
        'title' => 'Test Assignment',
        'submission_type' => 'text',
        'status' => 'published',
        'late_penalty_percent' => 30, // Assignment-specific penalty
    ]);
    SystemSetting::set('learning.late_penalty_percent', 10);
    // System default
    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'user_id' => $user->id,
        'enrollment_id' => $enrollment->id,
        'is_late' => true,
        'status' => 'late',
    ]);

    $graded = $this->service->grade($submission, 100, 'Good work');

    expect($graded->grade)->not->toBeNull();
    expect($graded->grade->score)->toEqual(70);
});

test('update throws exception for graded submission', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $assignment = Assignment::create([
        'lesson_id' => $lesson->id,
        'created_by' => $user->id,
        'title' => 'Test Assignment',
        'submission_type' => 'text',
    ]);
    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'user_id' => $user->id,
        'status' => 'graded',
    ]);

    expect(fn () => $this->service->update($submission, ['answer_text' => 'Updated']))
        ->toThrow(ValidationException::class);
});