<?php

use App\Policies\UnitPolicy;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Unit;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new UnitPolicy();
    createTestRoles();
});

test('superadmin can create unit', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');
    $course = Course::factory()->create();

    expect($this->policy->create($user, $course->id)->allowed())->toBeTrue();
});

test('admin can create unit for course they teach', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $course = Course::factory()->create(['instructor_id' => $user->id]);

    expect($this->policy->create($user, $course->id)->allowed())->toBeTrue();
});

test('admin cannot create unit for unmanaged course', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $course = Course::factory()->create();

    expect($this->policy->create($user, $course->id)->denied())->toBeTrue();
});

test('student cannot create unit', function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    $course = Course::factory()->create();

    expect($this->policy->create($user, $course->id)->denied())->toBeTrue();
});

test('superadmin can update any unit', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');
    $course = Course::factory()->create();
    $unit = Unit::factory()->create(['course_id' => $course->id]);

    expect($this->policy->update($user, $unit)->allowed())->toBeTrue();
});

test('admin can update unit in course they teach', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $course = Course::factory()->create(['instructor_id' => $user->id]);
    $unit = Unit::factory()->create(['course_id' => $course->id]);

    expect($this->policy->update($user, $unit)->allowed())->toBeTrue();
});

test('student cannot update unit', function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    $course = Course::factory()->create();
    $unit = Unit::factory()->create(['course_id' => $course->id]);

    expect($this->policy->update($user, $unit)->denied())->toBeTrue();
});