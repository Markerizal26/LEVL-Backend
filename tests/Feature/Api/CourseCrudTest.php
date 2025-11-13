<?php

use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    createTestRoles();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->student = User::factory()->create();
    $this->student->assignRole('student');
});

// ==================== POSITIVE TEST CASES ====================

// POST - Create Course
it('admin can create course with valid data', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/courses'), [
            'code' => 'TEST-001',
            'title' => 'Test Course',
            'level_tag' => 'dasar',
            'type' => 'okupasi',
            'enrollment_type' => 'auto_accept',
            'progression_mode' => 'free',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['course']])
        ->assertJsonPath('data.course.code', 'TEST-001')
        ->assertJsonPath('data.course.title', 'Test Course');

    assertDatabaseHas('courses', [
        'code' => 'TEST-001',
        'title' => 'Test Course',
    ]);
});

it('admin can create course with all fields', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/courses'), [
            'code' => 'TEST-002',
            'title' => 'Complete Course',
            'slug' => 'complete-course',
            'short_desc' => 'A complete course description',
            'level_tag' => 'mahir',
            'type' => 'kluster',
            'enrollment_type' => 'key_based',
            'enrollment_key' => 'SECRET123',
            'progression_mode' => 'sequential',
            'status' => 'draft',
        ]);

    $response->assertStatus(201);
    assertDatabaseHas('courses', [
        'code' => 'TEST-002',
        'slug' => 'complete-course',
        'enrollment_key' => 'SECRET123',
    ]);
});

it('admin can create course with outcomes and prerequisites', function () {
    $prerequisiteCourse = Course::factory()->create();

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/courses'), [
            'code' => 'TEST-OUTCOMES',
            'title' => 'Course with Outcomes',
            'level_tag' => 'dasar',
            'type' => 'okupasi',
            'enrollment_type' => 'auto_accept',
            'progression_mode' => 'free',
            'outcomes' => [
                'Learn Laravel basics',
                'Understand MVC pattern',
                'Build REST API',
            ],
            'prereq' => [$prerequisiteCourse->id],
        ]);

    $response->assertStatus(201);
    $course = Course::where('code', 'TEST-OUTCOMES')->first();
    
    expect($course->outcomes)->toHaveCount(3);
    expect($course->prerequisiteCourses)->toHaveCount(1);
    
    assertDatabaseHas('course_outcomes', [
        'course_id' => $course->id,
        'outcome_text' => 'Learn Laravel basics',
        'order' => 0,
    ]);
    
    assertDatabaseHas('course_prerequisites', [
        'course_id' => $course->id,
        'prerequisite_course_id' => $prerequisiteCourse->id,
    ]);
});

it('superadmin can create course', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');

    $response = $this->actingAs($superadmin, 'api')
        ->postJson(api('/courses'), [
            'code' => 'TEST-003',
            'title' => 'Superadmin Course',
            'level_tag' => 'dasar',
            'type' => 'okupasi',
            'enrollment_type' => 'auto_accept',
            'progression_mode' => 'free',
        ]);

    $response->assertStatus(201);
});

// PUT - Update Course
it('admin can update course', function () {
    $course = Course::factory()->create(['instructor_id' => $this->admin->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$course->slug}"), [
            'code' => $course->code,
            'title' => 'Updated Course Title',
            'level_tag' => 'mahir',
            'type' => 'okupasi',
            'enrollment_type' => 'auto_accept',
            'progression_mode' => 'free',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.course.title', 'Updated Course Title');

    assertDatabaseHas('courses', [
        'id' => $course->id,
        'title' => 'Updated Course Title',
    ]);
});

it('admin can update course with partial data', function () {
    $course = Course::factory()->create(['instructor_id' => $this->admin->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$course->slug}"), [
            'code' => $course->code,
            'title' => 'Partially Updated',
            'level_tag' => $course->level_tag,
            'type' => $course->type,
            'enrollment_type' => $course->enrollment_type,
            'progression_mode' => $course->progression_mode,
        ]);

    $response->assertStatus(200);
    assertDatabaseHas('courses', [
        'id' => $course->id,
        'title' => 'Partially Updated',
    ]);
});

it('admin can update course outcomes and prerequisites', function () {
    $course = Course::factory()->create(['instructor_id' => $this->admin->id]);
    $prerequisiteCourse1 = Course::factory()->create();
    $prerequisiteCourse2 = Course::factory()->create();

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$course->slug}"), [
            'code' => $course->code,
            'title' => $course->title,
            'level_tag' => $course->level_tag,
            'type' => $course->type,
            'enrollment_type' => $course->enrollment_type,
            'progression_mode' => $course->progression_mode,
            'outcomes' => [
                'Updated outcome 1',
                'Updated outcome 2',
            ],
            'prereq' => [$prerequisiteCourse1->id, $prerequisiteCourse2->id],
        ]);

    $response->assertStatus(200);
    $course->refresh();
    
    expect($course->outcomes)->toHaveCount(2);
    expect($course->prerequisiteCourses)->toHaveCount(2);
});

// DELETE - Delete Course
it('admin can delete course', function () {
    $course = Course::factory()->create(['instructor_id' => $this->admin->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api("/courses/{$course->slug}"));

    $response->assertStatus(200);
    assertDatabaseMissing('courses', ['id' => $course->id]);
});

it('superadmin can delete any course', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');
    $course = Course::factory()->create();

    $response = $this->actingAs($superadmin, 'api')
        ->deleteJson(api("/courses/{$course->slug}"));

    $response->assertStatus(200);
    assertDatabaseMissing('courses', ['id' => $course->id]);
});

// Publish/Unpublish
it('admin can publish course', function () {
    $course = Course::factory()->create([
        'instructor_id' => $this->admin->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$course->slug}/publish"));

    $response->assertStatus(200)
        ->assertJsonPath('data.course.status', 'published');

    assertDatabaseHas('courses', [
        'id' => $course->id,
        'status' => 'published',
    ]);
});

it('admin can unpublish course', function () {
    $course = Course::factory()->create([
        'instructor_id' => $this->admin->id,
        'status' => 'published',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$course->slug}/unpublish"));

    $response->assertStatus(200)
        ->assertJsonPath('data.course.status', 'draft');

    assertDatabaseHas('courses', [
        'id' => $course->id,
        'status' => 'draft',
    ]);
});

// ==================== NEGATIVE TEST CASES ====================

// POST - Create Course Negative
it('student cannot create course', function () {
    $response = $this->actingAs($this->student, 'api')
        ->postJson(api('/courses'), [
            'code' => 'TEST-004',
            'title' => 'Student Course',
            'level_tag' => 'dasar',
            'type' => 'okupasi',
            'enrollment_type' => 'auto_accept',
            'progression_mode' => 'free',
        ]);

    $response->assertStatus(403);
});

it('unauthenticated user cannot create course', function () {
    $response = $this->postJson(api('/courses'), [
        'code' => 'TEST-005',
        'title' => 'Unauthenticated Course',
        'level_tag' => 'dasar',
        'type' => 'okupasi',
        'enrollment_type' => 'auto_accept',
        'progression_mode' => 'free',
    ]);

    $response->assertStatus(401);
});

it('cannot create course with duplicate code', function () {
    $existing = Course::factory()->create(['code' => 'DUPLICATE-001']);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/courses'), [
            'code' => 'DUPLICATE-001',
            'title' => 'Duplicate Code Course',
            'level_tag' => 'dasar',
            'type' => 'okupasi',
            'enrollment_type' => 'auto_accept',
            'progression_mode' => 'free',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('cannot create course with invalid level_tag', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/courses'), [
            'code' => 'TEST-006',
            'title' => 'Invalid Level Course',
            'level_tag' => 'invalid_level',
            'type' => 'okupasi',
            'enrollment_type' => 'auto_accept',
            'progression_mode' => 'free',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['level_tag']);
});

it('cannot create course with missing required fields', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/courses'), [
            'title' => 'Missing Fields Course',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code', 'level_tag', 'type', 'enrollment_type', 'progression_mode']);
});

it('cannot create course with invalid enrollment_type', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/courses'), [
            'code' => 'TEST-007',
            'title' => 'Invalid Enrollment Course',
            'level_tag' => 'dasar',
            'type' => 'okupasi',
            'enrollment_type' => 'invalid_type',
            'progression_mode' => 'free',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['enrollment_type']);
});

it('requires enrollment_key for key_based enrollment_type', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/courses'), [
            'code' => 'TEST-008',
            'title' => 'Key Based Course',
            'level_tag' => 'dasar',
            'type' => 'okupasi',
            'enrollment_type' => 'key_based',
            'progression_mode' => 'free',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['enrollment_key']);
});

it('cannot create course with code exceeding max length', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/courses'), [
            'code' => str_repeat('A', 51), // Max 50
            'title' => 'Long Code Course',
            'level_tag' => 'dasar',
            'type' => 'okupasi',
            'enrollment_type' => 'auto_accept',
            'progression_mode' => 'free',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

// PUT - Update Course Negative
it('student cannot update course', function () {
    $course = Course::factory()->create();

    $response = $this->actingAs($this->student, 'api')
        ->putJson(api("/courses/{$course->slug}"), [
            'code' => $course->code,
            'title' => 'Updated by Student',
            'level_tag' => $course->level_tag,
            'type' => $course->type,
            'enrollment_type' => $course->enrollment_type,
            'progression_mode' => $course->progression_mode,
        ]);

    $response->assertStatus(403);
});

it('admin cannot update course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $course = Course::factory()->create(['instructor_id' => $otherAdmin->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$course->slug}"), [
            'code' => $course->code,
            'title' => 'Unauthorized Update',
            'level_tag' => $course->level_tag,
            'type' => $course->type,
            'enrollment_type' => $course->enrollment_type,
            'progression_mode' => $course->progression_mode,
        ]);

    $response->assertStatus(403);
});

it('cannot update course with duplicate code', function () {
    $course1 = Course::factory()->create(['instructor_id' => $this->admin->id]);
    $course2 = Course::factory()->create(['code' => 'DUPLICATE-002']);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$course1->slug}"), [
            'code' => 'DUPLICATE-002',
            'title' => $course1->title,
            'level_tag' => $course1->level_tag,
            'type' => $course1->type,
            'enrollment_type' => $course1->enrollment_type,
            'progression_mode' => $course1->progression_mode,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('cannot update non-existent course', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api('/courses/non-existent-slug'), [
            'code' => 'TEST-009',
            'title' => 'Non Existent',
            'level_tag' => 'dasar',
            'type' => 'okupasi',
            'enrollment_type' => 'auto_accept',
            'progression_mode' => 'free',
        ]);

    $response->assertStatus(404);
});

// DELETE - Delete Course Negative
it('student cannot delete course', function () {
    $course = Course::factory()->create();

    $response = $this->actingAs($this->student, 'api')
        ->deleteJson(api("/courses/{$course->slug}"));

    $response->assertStatus(403);
});

it('admin cannot delete course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $course = Course::factory()->create(['instructor_id' => $otherAdmin->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api("/courses/{$course->slug}"));

    $response->assertStatus(403);
});

it('cannot delete non-existent course', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api('/courses/non-existent-slug'));

    $response->assertStatus(404);
});

// Publish/Unpublish Negative
it('student cannot publish course', function () {
    $course = Course::factory()->create(['status' => 'draft']);

    $response = $this->actingAs($this->student, 'api')
        ->putJson(api("/courses/{$course->slug}/publish"));

    $response->assertStatus(403);
});

it('admin cannot publish course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $course = Course::factory()->create([
        'instructor_id' => $otherAdmin->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$course->slug}/publish"));

    $response->assertStatus(403);
});

