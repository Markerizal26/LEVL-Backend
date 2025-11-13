<?php

use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    createTestRoles();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->student = User::factory()->create();
    $this->student->assignRole('student');
    $this->course = Course::factory()->create([
        'instructor_id' => $this->admin->id,
        'enrollment_type' => 'auto_accept',
    ]);
});

// ==================== POSITIVE TEST CASES ====================

// POST - Enroll
it('student can enroll in auto_accept course', function () {
    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/courses/{$this->course->slug}/enrollments"));

    $response->assertStatus(200)
        ->assertJsonPath('data.enrollment.status', 'active');

    assertDatabaseHas('enrollments', [
        'user_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
    ]);
});

it('student can enroll in key_based course with correct key', function () {
    $course = Course::factory()->create([
        'enrollment_type' => 'key_based',
        'enrollment_key' => 'SECRET123',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/courses/{$course->slug}/enrollments"), [
            'enrollment_key' => 'SECRET123',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.enrollment.status', 'active');
});

it('student can enroll in approval course', function () {
    $course = Course::factory()->create([
        'enrollment_type' => 'approval',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/courses/{$course->slug}/enrollments"));

    $response->assertStatus(200)
        ->assertJsonPath('data.enrollment.status', 'pending');
});

// POST - Cancel Enrollment
it('student can cancel their enrollment', function () {
    $enrollment = Enrollment::factory()->create([
        'user_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/courses/{$this->course->slug}/cancel"));

    $response->assertStatus(200);
    assertDatabaseHas('enrollments', [
        'id' => $enrollment->id,
        'status' => 'cancelled',
    ]);
});

// POST - Approve Enrollment
it('admin can approve pending enrollment', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $this->course->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/enrollments/{$enrollment->id}/approve"));

    $response->assertStatus(200)
        ->assertJsonPath('data.enrollment.status', 'active');
});

// POST - Decline Enrollment
it('admin can decline pending enrollment', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $this->course->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/enrollments/{$enrollment->id}/decline"));

    $response->assertStatus(200)
        ->assertJsonPath('data.enrollment.status', 'cancelled');
});

// ==================== NEGATIVE TEST CASES ====================

// POST - Enroll Negative
it('cannot enroll in key_based course without key', function () {
    $course = Course::factory()->create([
        'enrollment_type' => 'key_based',
        'enrollment_key' => 'SECRET123',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/courses/{$course->slug}/enrollments"));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['enrollment_key']);
});

it('cannot enroll in key_based course with wrong key', function () {
    $course = Course::factory()->create([
        'enrollment_type' => 'key_based',
        'enrollment_key' => 'SECRET123',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/courses/{$course->slug}/enrollments"), [
            'enrollment_key' => 'WRONG_KEY',
        ]);

    $response->assertStatus(422);
});

it('cannot enroll twice in same course', function () {
    Enrollment::factory()->create([
        'user_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/courses/{$this->course->slug}/enrollments"));

    $response->assertStatus(422);
});

it('unauthenticated user cannot enroll', function () {
    $response = $this->postJson(api("/courses/{$this->course->slug}/enrollments"));

    $response->assertStatus(401);
});

// POST - Cancel Negative
it('cannot cancel non-existent enrollment', function () {
    // Student has no enrollment in this course
    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/courses/{$this->course->slug}/cancel"));

    // Should return 404 or 422 depending on implementation
    expect($response->status())->toBeIn([404, 422]);
});

it('cannot cancel enrollment of other user', function () {
    $otherStudent = User::factory()->create();
    $otherStudent->assignRole('student');
    Enrollment::factory()->create([
        'user_id' => $otherStudent->id,
        'course_id' => $this->course->id,
    ]);

    // Current student has no enrollment, so should return 404 or 422
    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/courses/{$this->course->slug}/cancel"));

    expect($response->status())->toBeIn([404, 422]);
});

// POST - Approve/Decline Negative
it('student cannot approve enrollment', function () {
    $otherStudent = User::factory()->create();
    $otherStudent->assignRole('student');
    $enrollment = Enrollment::factory()->create([
        'user_id' => $otherStudent->id,
        'course_id' => $this->course->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/enrollments/{$enrollment->id}/approve"));

    $response->assertStatus(403);
});

it('cannot approve non-pending enrollment', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/enrollments/{$enrollment->id}/approve"));

    $response->assertStatus(422);
});

it('cannot approve non-existent enrollment', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/enrollments/99999/approve'));

    $response->assertStatus(404);
});

it('admin cannot approve enrollment in course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $otherCourse = Course::factory()->create(['instructor_id' => $otherAdmin->id]);
    $student = User::factory()->create();
    $student->assignRole('student');
    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $otherCourse->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/enrollments/{$enrollment->id}/approve"));

    $response->assertStatus(403);
});

it('cannot decline non-existent enrollment', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/enrollments/99999/decline'));

    $response->assertStatus(404);
});

it('admin cannot decline enrollment in course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $otherCourse = Course::factory()->create(['instructor_id' => $otherAdmin->id]);
    $student = User::factory()->create();
    $student->assignRole('student');
    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $otherCourse->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/enrollments/{$enrollment->id}/decline"));

    $response->assertStatus(403);
});

it('cannot decline non-pending enrollment', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/enrollments/{$enrollment->id}/decline"));

    $response->assertStatus(422);
});

it('student cannot decline enrollment', function () {
    $otherStudent = User::factory()->create();
    $otherStudent->assignRole('student');
    $enrollment = Enrollment::factory()->create([
        'user_id' => $otherStudent->id,
        'course_id' => $this->course->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/enrollments/{$enrollment->id}/decline"));

    $response->assertStatus(403);
});

