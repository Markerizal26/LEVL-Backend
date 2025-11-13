<?php

use Modules\Auth\Models\User;
use Modules\Schemes\Models\Tag;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    createTestRoles();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->student = User::factory()->create();
    $this->student->assignRole('student');
});

// ==================== POSITIVE TEST CASES ====================

// POST - Create Tag
it('admin can create tag', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/course-tags'), [
            'name' => 'Laravel',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['tag']])
        ->assertJsonPath('data.tag.name', 'Laravel');

    assertDatabaseHas('tags', [
        'name' => 'Laravel',
    ]);
});

it('admin can create multiple tags at once', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/course-tags'), [
            'names' => ['PHP', 'JavaScript', 'Python'],
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['tags']]);

    assertDatabaseHas('tags', ['name' => 'PHP']);
    assertDatabaseHas('tags', ['name' => 'JavaScript']);
    assertDatabaseHas('tags', ['name' => 'Python']);
});

// PUT - Update Tag
it('admin can update tag', function () {
    $tag = Tag::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/course-tags/{$tag->slug}"), [
            'name' => 'New Name',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.tag.name', 'New Name');

    assertDatabaseHas('tags', [
        'id' => $tag->id,
        'name' => 'New Name',
    ]);
});

// DELETE - Delete Tag
it('admin can delete tag', function () {
    $tag = Tag::factory()->create();

    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api("/course-tags/{$tag->slug}"));

    $response->assertStatus(200);
    assertDatabaseMissing('tags', ['id' => $tag->id]);
});

// ==================== NEGATIVE TEST CASES ====================

// POST - Create Tag Negative
it('student cannot create tag', function () {
    $response = $this->actingAs($this->student, 'api')
        ->postJson(api('/course-tags'), [
            'name' => 'Student Tag',
        ]);

    $response->assertStatus(403);
});

it('cannot create tag with duplicate name', function () {
    Tag::factory()->create(['name' => 'Duplicate Tag']);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/course-tags'), [
            'name' => 'Duplicate Tag',
        ]);

    // Should return validation error for duplicate name
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('cannot create tag with missing name', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/course-tags'), []);

    $response->assertStatus(422);
});

it('unauthenticated user cannot create tag', function () {
    $response = $this->postJson(api('/course-tags'), [
        'name' => 'Unauthenticated Tag',
    ]);

    $response->assertStatus(401);
});

// PUT - Update Tag Negative
it('student cannot update tag', function () {
    $tag = Tag::factory()->create();

    $response = $this->actingAs($this->student, 'api')
        ->putJson(api("/course-tags/{$tag->slug}"), [
            'name' => 'Updated by Student',
        ]);

    $response->assertStatus(403);
});

it('cannot update non-existent tag', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api('/course-tags/non-existent-slug'), [
            'name' => 'Non Existent',
        ]);

    $response->assertStatus(404);
});

it('cannot update tag with duplicate name', function () {
    $tag1 = Tag::factory()->create(['name' => 'Tag One']);
    $tag2 = Tag::factory()->create(['name' => 'Tag Two']);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/course-tags/{$tag1->slug}"), [
            'name' => 'Tag Two',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

// DELETE - Delete Tag Negative
it('student cannot delete tag', function () {
    $tag = Tag::factory()->create();

    $response = $this->actingAs($this->student, 'api')
        ->deleteJson(api("/course-tags/{$tag->slug}"));

    $response->assertStatus(403);
});

it('cannot delete non-existent tag', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api('/course-tags/non-existent-slug'));

    $response->assertStatus(404);
});

it('unauthenticated user cannot delete tag', function () {
    $tag = Tag::factory()->create();

    $response = $this->deleteJson(api("/course-tags/{$tag->slug}"));

    $response->assertStatus(401);
});

