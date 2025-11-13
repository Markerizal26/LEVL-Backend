<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Modules\Enrollments\Events\EnrollmentCreated;
use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Services\EnrollmentService;
use Modules\Schemes\Models\Course;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = new EnrollmentService();
    Mail::fake();
    Event::fake();
});

test('enroll creates active enrollment for auto accept course', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create(['enrollment_type' => 'auto_accept']);

    $result = $this->service->enroll($course, $user);

    expect($result['status'])->toEqual('active');
    assertDatabaseHas('enrollments', [
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);
});

test('enroll creates pending enrollment for approval course', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create(['enrollment_type' => 'approval']);

    $result = $this->service->enroll($course, $user);

    expect($result['status'])->toEqual('pending');
    assertDatabaseHas('enrollments', [
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'pending',
    ]);
});

test('enroll validates enrollment key for key based course', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create([
        'enrollment_type' => 'key_based',
        'enrollment_key' => 'secret-key-123',
    ]);

    expect(fn () => $this->service->enroll($course, $user, ['enrollment_key' => 'wrong-key']))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('enroll creates active enrollment with correct key', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create([
        'enrollment_type' => 'key_based',
        'enrollment_key' => 'secret-key-123',
    ]);

    $result = $this->service->enroll($course, $user, ['enrollment_key' => 'secret-key-123']);

    expect($result['status'])->toEqual('active');
});

test('enroll dispatches enrollment created event', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create(['enrollment_type' => 'auto_accept']);

    $this->service->enroll($course, $user);

    Event::assertDispatched(EnrollmentCreated::class);
});

test('approve changes pending to active', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'pending',
    ]);

    $result = $this->service->approve($enrollment);

    expect($result->status)->toEqual('active');
    expect($result->enrolled_at)->not->toBeNull();
});

test('approve throws exception for non pending enrollment', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    expect(fn () => $this->service->approve($enrollment))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('decline changes pending to cancelled', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'pending',
    ]);

    $result = $this->service->decline($enrollment);

    expect($result->status)->toEqual('cancelled');
});

test('cancel changes pending to cancelled', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'pending',
    ]);

    $result = $this->service->cancel($enrollment);

    expect($result->status)->toEqual('cancelled');
});

test('cancel throws exception for non pending enrollment', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    expect(fn () => $this->service->cancel($enrollment))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('withdraw changes active to cancelled', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    $result = $this->service->withdraw($enrollment);

    expect($result->status)->toEqual('cancelled');
});

test('withdraw throws exception for non active enrollment', function () {
    $user = \Modules\Auth\Models\User::factory()->create();
    $course = Course::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'pending',
    ]);

    expect(fn () => $this->service->withdraw($enrollment))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});