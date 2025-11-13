<?php

use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Unit;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    createTestRoles();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->student = User::factory()->create();
    $this->student->assignRole('student');
    $this->course = Course::factory()->create(['instructor_id' => $this->admin->id]);
});

// ==================== POSITIVE TEST CASES ====================

// POST - Create Unit
it('admin can create unit for their course', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/courses/{$this->course->slug}/units"), [
            'code' => 'UNIT-001',
            'title' => 'Test Unit',
            'description' => 'Unit description',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['unit']])
        ->assertJsonPath('data.unit.code', 'UNIT-001')
        ->assertJsonPath('data.unit.title', 'Test Unit');

    assertDatabaseHas('units', [
        'course_id' => $this->course->id,
        'code' => 'UNIT-001',
        'title' => 'Test Unit',
    ]);
});

it('superadmin can create unit for any course', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');
    $course = Course::factory()->create();

    $response = $this->actingAs($superadmin, 'api')
        ->postJson(api("/courses/{$course->slug}/units"), [
            'code' => 'UNIT-002',
            'title' => 'Superadmin Unit',
        ]);

    $response->assertStatus(201);
});

// PUT - Update Unit
it('admin can update unit in their course', function () {
    $unit = Unit::factory()->create(['course_id' => $this->course->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$this->course->slug}/units/{$unit->slug}"), [
            'code' => $unit->code,
            'title' => 'Updated Unit Title',
            'description' => 'Updated description',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.unit.title', 'Updated Unit Title');

    assertDatabaseHas('units', [
        'id' => $unit->id,
        'title' => 'Updated Unit Title',
    ]);
});

// DELETE - Delete Unit
it('admin can delete unit from their course', function () {
    $unit = Unit::factory()->create(['course_id' => $this->course->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api("/courses/{$this->course->slug}/units/{$unit->slug}"));

    $response->assertStatus(200);
    assertDatabaseMissing('units', ['id' => $unit->id]);
});

// Publish/Unpublish
it('admin can publish unit', function () {
    $unit = Unit::factory()->create([
        'course_id' => $this->course->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$this->course->slug}/units/{$unit->slug}/publish"));

    $response->assertStatus(200)
        ->assertJsonPath('data.unit.status', 'published');
});

// ==================== NEGATIVE TEST CASES ====================

// POST - Create Unit Negative
it('student cannot create unit', function () {
    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/courses/{$this->course->slug}/units"), [
            'code' => 'UNIT-003',
            'title' => 'Student Unit',
        ]);

    $response->assertStatus(403);
});

it('admin cannot create unit for course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $otherCourse = Course::factory()->create(['instructor_id' => $otherAdmin->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/courses/{$otherCourse->slug}/units"), [
            'code' => 'UNIT-004',
            'title' => 'Unauthorized Unit',
        ]);

    $response->assertStatus(403);
});

it('cannot create unit with duplicate code', function () {
    Unit::factory()->create([
        'course_id' => $this->course->id,
        'code' => 'DUPLICATE-UNIT',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/courses/{$this->course->slug}/units"), [
            'code' => 'DUPLICATE-UNIT',
            'title' => 'Duplicate Unit',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('cannot create unit with missing required fields', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/courses/{$this->course->slug}/units"), [
            'title' => 'Missing Fields Unit',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

// PUT - Update Unit Negative
it('student cannot update unit', function () {
    $unit = Unit::factory()->create(['course_id' => $this->course->id]);

    $response = $this->actingAs($this->student, 'api')
        ->putJson(api("/courses/{$this->course->slug}/units/{$unit->slug}"), [
            'code' => $unit->code,
            'title' => 'Updated by Student',
        ]);

    $response->assertStatus(403);
});

it('admin cannot update unit in course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $otherCourse = Course::factory()->create(['instructor_id' => $otherAdmin->id]);
    $unit = Unit::factory()->create(['course_id' => $otherCourse->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$otherCourse->slug}/units/{$unit->slug}"), [
            'code' => $unit->code,
            'title' => 'Unauthorized Update',
        ]);

    $response->assertStatus(403);
});

it('cannot update non-existent unit', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$this->course->slug}/units/non-existent-slug"), [
            'code' => 'TEST-CODE',
            'title' => 'Non Existent',
        ]);

    $response->assertStatus(404);
});

it('cannot update unit with duplicate code', function () {
    $unit1 = Unit::factory()->create(['course_id' => $this->course->id, 'code' => 'UNIT-001']);
    $unit2 = Unit::factory()->create(['course_id' => $this->course->id, 'code' => 'UNIT-002']);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$this->course->slug}/units/{$unit1->slug}"), [
            'code' => 'UNIT-002',
            'title' => $unit1->title,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

// DELETE - Delete Unit Negative
it('student cannot delete unit', function () {
    $unit = Unit::factory()->create(['course_id' => $this->course->id]);

    $response = $this->actingAs($this->student, 'api')
        ->deleteJson(api("/courses/{$this->course->slug}/units/{$unit->slug}"));

    $response->assertStatus(403);
});

it('admin cannot delete unit from course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $otherCourse = Course::factory()->create(['instructor_id' => $otherAdmin->id]);
    $unit = Unit::factory()->create(['course_id' => $otherCourse->id]);

    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api("/courses/{$otherCourse->slug}/units/{$unit->slug}"));

    $response->assertStatus(403);
});

it('cannot delete non-existent unit', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api("/courses/{$this->course->slug}/units/non-existent-slug"));

    $response->assertStatus(404);
});

// Publish/Unpublish Negative
it('student cannot publish unit', function () {
    $unit = Unit::factory()->create([
        'course_id' => $this->course->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->putJson(api("/courses/{$this->course->slug}/units/{$unit->slug}/publish"));

    $response->assertStatus(403);
});

it('admin cannot publish unit in course they dont manage', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $otherCourse = Course::factory()->create(['instructor_id' => $otherAdmin->id]);
    $unit = Unit::factory()->create([
        'course_id' => $otherCourse->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/courses/{$otherCourse->slug}/units/{$unit->slug}/publish"));

    $response->assertStatus(403);
});

