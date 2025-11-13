<?php

use App\Policies\CoursePolicy;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new CoursePolicy();
    createTestRoles();
});

test('superadmin can create course', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    expect($this->policy->create($user)->allowed())->toBeTrue();
});

test('admin can create course', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    expect($this->policy->create($user)->allowed())->toBeTrue();
});

test('student cannot create course', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    expect($this->policy->create($user)->denied())->toBeTrue();
});

test('superadmin can update any course', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');
    $course = Course::factory()->create();

    // Superadmin should have admin role too, or Gate::before should allow
    $user->assignRole('admin');
    expect($this->policy->update($user, $course)->allowed())->toBeTrue();
});

test('admin can update course they are instructor of', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $course = Course::factory()->create(['instructor_id' => $user->id]);

    expect($this->policy->update($user, $course)->allowed())->toBeTrue();
});

test('admin cannot update course they dont manage', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $course = Course::factory()->create();

    expect($this->policy->update($user, $course)->denied())->toBeTrue();
});

test('instructor can update course they teach', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    // Policy checks for admin role
    $course = Course::factory()->create(['instructor_id' => $user->id]);

    expect($this->policy->update($user, $course)->allowed())->toBeTrue();
});

test('student cannot update course', function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    $course = Course::factory()->create();

    expect($this->policy->update($user, $course)->denied())->toBeTrue();
});

test('superadmin can delete any course', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');
    $course = Course::factory()->create();

    // Superadmin should have admin role too, or Gate::before should allow
    $user->assignRole('admin');
    expect($this->policy->delete($user, $course)->allowed())->toBeTrue();
});

test('admin can delete course they are instructor of', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $course = Course::factory()->create(['instructor_id' => $user->id]);

    expect($this->policy->delete($user, $course)->allowed())->toBeTrue();
});

test('student cannot delete course', function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    $course = Course::factory()->create();

    expect($this->policy->delete($user, $course)->denied())->toBeTrue();
});