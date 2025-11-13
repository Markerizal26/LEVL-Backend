<?php

use Modules\Auth\Models\User;
use Modules\Learning\Models\Assignment;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    createTestRoles();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->student = User::factory()->create();
    $this->student->assignRole('student');
    $this->course = Course::factory()->create(['instructor_id' => $this->admin->id]);
    $this->unit = Unit::factory()->create(['course_id' => $this->course->id]);
    $this->lesson = Lesson::factory()->create(['unit_id' => $this->unit->id]);
});

// ==================== POSITIVE TEST CASES ====================

// POST - Create Assignment
it('admin can create assignment', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons/{$this->lesson->slug}/assignments"), [
            'title' => 'Test Assignment',
            'description' => 'Assignment description',
            'submission_type' => 'text',
            'max_score' => 100,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['assignment']])
        ->assertJsonPath('data.assignment.title', 'Test Assignment');

    assertDatabaseHas('assignments', [
        'lesson_id' => $this->lesson->id,
        'title' => 'Test Assignment',
    ]);
});

// PUT - Update Assignment
it('admin can update assignment', function () {
    $assignment = Assignment::create([
        'lesson_id' => $this->lesson->id,
        'created_by' => $this->admin->id,
        'title' => 'Original Assignment',
        'submission_type' => 'text',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/assignments/{$assignment->id}"), [
            'title' => 'Updated Assignment',
            'max_score' => 75,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.assignment.title', 'Updated Assignment');

    assertDatabaseHas('assignments', [
        'id' => $assignment->id,
        'title' => 'Updated Assignment',
        'max_score' => 75,
    ]);
});

// DELETE - Delete Assignment
it('admin can delete assignment', function () {
    $assignment = Assignment::create([
        'lesson_id' => $this->lesson->id,
        'created_by' => $this->admin->id,
        'title' => 'To Delete',
        'submission_type' => 'text',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api("/assignments/{$assignment->id}"));

    $response->assertStatus(200);
    assertDatabaseMissing('assignments', ['id' => $assignment->id]);
});

// Publish/Unpublish
it('admin can publish assignment', function () {
    $assignment = Assignment::create([
        'lesson_id' => $this->lesson->id,
        'created_by' => $this->admin->id,
        'title' => 'Draft Assignment',
        'submission_type' => 'text',
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/assignments/{$assignment->id}/publish"));

    $response->assertStatus(200)
        ->assertJsonPath('data.assignment.status', 'published');
});

// ==================== NEGATIVE TEST CASES ====================

// POST - Create Assignment Negative
it('student cannot create assignment', function () {
    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons/{$this->lesson->slug}/assignments"), [
            'title' => 'Student Assignment',
            'submission_type' => 'text',
        ]);

    $response->assertStatus(403);
});

it('cannot create assignment with missing required fields', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons/{$this->lesson->slug}/assignments"), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

it('unauthenticated user cannot create assignment', function () {
    $response = $this->postJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons/{$this->lesson->slug}/assignments"), [
        'title' => 'Unauthenticated Assignment',
        'submission_type' => 'text',
    ]);

    $response->assertStatus(401);
});

it('admin cannot create assignment in course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $otherCourse = Course::factory()->create(['instructor_id' => $otherAdmin->id]);
    $otherUnit = Unit::factory()->create(['course_id' => $otherCourse->id]);
    $otherLesson = Lesson::factory()->create(['unit_id' => $otherUnit->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/courses/{$otherCourse->slug}/units/{$otherUnit->slug}/lessons/{$otherLesson->slug}/assignments"), [
            'title' => 'Unauthorized Assignment',
            'submission_type' => 'text',
        ]);

    $response->assertStatus(403);
});

// PUT - Update Assignment Negative
it('student cannot update assignment', function () {
    $assignment = Assignment::create([
        'lesson_id' => $this->lesson->id,
        'created_by' => $this->admin->id,
        'title' => 'Original',
        'submission_type' => 'text',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->putJson(api("/assignments/{$assignment->id}"), [
            'title' => 'Updated by Student',
        ]);

    $response->assertStatus(403);
});

it('cannot update non-existent assignment', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api('/assignments/99999'), [
            'title' => 'Non Existent',
        ]);

    $response->assertStatus(404);
});

it('admin cannot update assignment they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $otherCourse = Course::factory()->create(['instructor_id' => $otherAdmin->id]);
    $otherUnit = Unit::factory()->create(['course_id' => $otherCourse->id]);
    $otherLesson = Lesson::factory()->create(['unit_id' => $otherUnit->id]);
    $assignment = Assignment::create([
        'lesson_id' => $otherLesson->id,
        'created_by' => $otherAdmin->id,
        'title' => 'Other Assignment',
        'submission_type' => 'text',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/assignments/{$assignment->id}"), [
            'title' => 'Unauthorized Update',
        ]);

    $response->assertStatus(403);
});

// DELETE - Delete Assignment Negative
it('student cannot delete assignment', function () {
    $assignment = Assignment::create([
        'lesson_id' => $this->lesson->id,
        'created_by' => $this->admin->id,
        'title' => 'To Delete',
        'submission_type' => 'text',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->deleteJson(api("/assignments/{$assignment->id}"));

    $response->assertStatus(403);
});

it('cannot delete non-existent assignment', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api('/assignments/99999'));

    $response->assertStatus(404);
});

it('admin cannot delete assignment they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $otherCourse = Course::factory()->create(['instructor_id' => $otherAdmin->id]);
    $otherUnit = Unit::factory()->create(['course_id' => $otherCourse->id]);
    $otherLesson = Lesson::factory()->create(['unit_id' => $otherUnit->id]);
    $assignment = Assignment::create([
        'lesson_id' => $otherLesson->id,
        'created_by' => $otherAdmin->id,
        'title' => 'Other Assignment',
        'submission_type' => 'text',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api("/assignments/{$assignment->id}"));

    $response->assertStatus(403);
});

// Publish/Unpublish Negative
it('student cannot publish assignment', function () {
    $assignment = Assignment::create([
        'lesson_id' => $this->lesson->id,
        'created_by' => $this->admin->id,
        'title' => 'Draft Assignment',
        'submission_type' => 'text',
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->putJson(api("/assignments/{$assignment->id}/publish"));

    $response->assertStatus(403);
});

it('admin can unpublish assignment', function () {
    $assignment = Assignment::create([
        'lesson_id' => $this->lesson->id,
        'created_by' => $this->admin->id,
        'title' => 'Published Assignment',
        'submission_type' => 'text',
        'status' => 'published',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/assignments/{$assignment->id}/unpublish"));

    $response->assertStatus(200)
        ->assertJsonPath('data.assignment.status', 'draft');
});

