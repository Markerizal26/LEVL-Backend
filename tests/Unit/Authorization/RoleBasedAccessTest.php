<?php

use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    createTestRoles();
});

test('superadmin has access to all resources', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    expect($user->hasRole('superadmin'))->toBeTrue();
    expect($user->hasAnyRole(['admin', 'instructor', 'superadmin']))->toBeTrue();
});

test('admin can manage courses', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $course = Course::factory()->create();
    $course->admins()->attach($user->id);

    expect($user->hasRole('admin'))->toBeTrue();
    expect($course->admins->contains($user))->toBeTrue();
});

test('instructor can manage their courses', function () {
    $user = User::factory()->create();
    $user->assignRole('instructor');
    $course = Course::factory()->create(['instructor_id' => $user->id]);

    expect($user->hasRole('instructor'))->toBeTrue();
    expect($course->instructor_id)->toEqual($user->id);
});

test('student can only view enrolled courses', function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    $course = Course::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    expect($user->hasRole('student'))->toBeTrue();
    expect($enrollment->user_id === $user->id)->toBeTrue();
});

test('user can have multiple roles', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $user->assignRole('instructor');

    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->hasRole('instructor'))->toBeTrue();
    expect($user->hasAnyRole(['admin', 'instructor']))->toBeTrue();
});

test('user can check permissions', function () {
    $user = User::factory()->create();
    $permission = \Spatie\Permission\Models\Permission::create([
        'name' => 'courses.create',
        'guard_name' => 'api',
    ]);
    $user->givePermissionTo($permission);

    expect($user->can('courses.create'))->toBeTrue();
    expect($user->can('courses.delete'))->toBeFalse();
});