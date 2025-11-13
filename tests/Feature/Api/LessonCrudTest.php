<?php

use Modules\Auth\Models\User;
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
});

// ==================== POSITIVE TEST CASES ====================

// POST - Create Lesson
it('admin can create lesson in their course unit', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons"), [
            'title' => 'Test Lesson',
            'description' => 'Lesson description',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['lesson']])
        ->assertJsonPath('data.lesson.title', 'Test Lesson');

    assertDatabaseHas('lessons', [
        'unit_id' => $this->unit->id,
        'title' => 'Test Lesson',
    ]);
});

// PUT - Update Lesson
it('admin can update lesson in their course', function () {
    $lesson = Lesson::factory()->create(['unit_id' => $this->unit->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons/{$lesson->slug}"), [
            'title' => 'Updated Lesson Title',
            'description' => 'Updated description',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.lesson.title', 'Updated Lesson Title');

    assertDatabaseHas('lessons', [
        'id' => $lesson->id,
        'title' => 'Updated Lesson Title',
    ]);
});

// DELETE - Delete Lesson
it('admin can delete lesson from their course', function () {
    $lesson = Lesson::factory()->create(['unit_id' => $this->unit->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons/{$lesson->slug}"));

    $response->assertStatus(200);
    assertDatabaseMissing('lessons', ['id' => $lesson->id]);
});

// Publish/Unpublish
it('admin can publish lesson', function () {
    $lesson = Lesson::factory()->create([
        'unit_id' => $this->unit->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons/{$lesson->slug}/publish"));

    $response->assertStatus(200)
        ->assertJsonPath('data.lesson.status', 'published');
});

// ==================== NEGATIVE TEST CASES ====================

// POST - Create Lesson Negative
it('student cannot create lesson', function () {
    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons"), [
            'title' => 'Student Lesson',
        ]);

    $response->assertStatus(403);
});

it('admin cannot create lesson in course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $otherCourse = Course::factory()->create(['instructor_id' => $otherAdmin->id]);
    $otherUnit = Unit::factory()->create(['course_id' => $otherCourse->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/courses/{$otherCourse->slug}/units/{$otherUnit->slug}/lessons"), [
            'title' => 'Unauthorized Lesson',
        ]);

    $response->assertStatus(403);
});

it('cannot create lesson with missing required fields', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons"), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

it('unauthenticated user cannot create lesson', function () {
    $response = $this->postJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons"), [
        'title' => 'Unauthenticated Lesson',
    ]);

    $response->assertStatus(401);
});

// PUT - Update Lesson Negative
it('student cannot update lesson', function () {
    $lesson = Lesson::factory()->create(['unit_id' => $this->unit->id]);

    $response = $this->actingAs($this->student, 'api')
        ->putJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons/{$lesson->slug}"), [
            'title' => 'Updated by Student',
        ]);

    $response->assertStatus(403);
});

it('admin cannot update lesson in course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $otherCourse = Course::factory()->create(['instructor_id' => $otherAdmin->id]);
    $otherUnit = Unit::factory()->create(['course_id' => $otherCourse->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $otherUnit->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$otherCourse->slug}/units/{$otherUnit->slug}/lessons/{$lesson->slug}"), [
            'title' => 'Unauthorized Update',
        ]);

    $response->assertStatus(403);
});

it('cannot update non-existent lesson', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons/non-existent-slug"), [
            'title' => 'Non Existent',
        ]);

    $response->assertStatus(404);
});

// DELETE - Delete Lesson Negative
it('student cannot delete lesson', function () {
    $lesson = Lesson::factory()->create(['unit_id' => $this->unit->id]);

    $response = $this->actingAs($this->student, 'api')
        ->deleteJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons/{$lesson->slug}"));

    $response->assertStatus(403);
});

it('admin cannot delete lesson from course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $otherCourse = Course::factory()->create(['instructor_id' => $otherAdmin->id]);
    $otherUnit = Unit::factory()->create(['course_id' => $otherCourse->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $otherUnit->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api("/courses/{$otherCourse->slug}/units/{$otherUnit->slug}/lessons/{$lesson->slug}"));

    $response->assertStatus(403);
});

it('cannot delete non-existent lesson', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons/non-existent-slug"));

    $response->assertStatus(404);
});

// Publish/Unpublish Negative
it('student cannot publish lesson', function () {
    $lesson = Lesson::factory()->create([
        'unit_id' => $this->unit->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->putJson(api("/courses/{$this->course->slug}/units/{$this->unit->slug}/lessons/{$lesson->slug}/publish"));

    $response->assertStatus(403);
});

it('admin cannot publish lesson in course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $otherCourse = Course::factory()->create(['instructor_id' => $otherAdmin->id]);
    $otherUnit = Unit::factory()->create(['course_id' => $otherCourse->id]);
    $lesson = Lesson::factory()->create([
        'unit_id' => $otherUnit->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$otherCourse->slug}/units/{$otherUnit->slug}/lessons/{$lesson->slug}/publish"));

    $response->assertStatus(403);
});

