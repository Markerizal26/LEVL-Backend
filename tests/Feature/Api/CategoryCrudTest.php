<?php

use Modules\Auth\Models\User;
use Modules\Common\Models\Category;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    createTestRoles();
    $this->superadmin = User::factory()->create();
    $this->superadmin->assignRole('superadmin');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

// ==================== POSITIVE TEST CASES ====================

// POST - Create Category
it('superadmin can create category', function () {
    $response = $this->actingAs($this->superadmin, 'api')
        ->postJson(api('/categories'), [
            'name' => 'Technology',
            'value' => 'technology',
            'description' => 'Tech related courses',
            'status' => 'active',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['category']])
        ->assertJsonPath('data.category.name', 'Technology')
        ->assertJsonPath('data.category.value', 'technology');

    assertDatabaseHas('categories', [
        'name' => 'Technology',
        'value' => 'technology',
    ]);
});

// PUT - Update Category
it('superadmin can update category', function () {
    $category = Category::create([
        'name' => 'Old Name',
        'value' => 'old-value',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->superadmin, 'api')
        ->putJson(api("/categories/{$category->id}"), [
            'name' => 'New Name',
            'value' => 'new-value',
            'description' => 'Updated description',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.category.name', 'New Name');

    assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'New Name',
        'value' => 'new-value',
    ]);
});

// DELETE - Delete Category
it('superadmin can delete category', function () {
    $category = Category::create([
        'name' => 'To Delete',
        'value' => 'to-delete',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->superadmin, 'api')
        ->deleteJson(api("/categories/{$category->id}"));

    $response->assertStatus(200);
    // Category uses SoftDeletes, so check deleted_at
    $category->refresh();
    expect($category->trashed())->toBeTrue();
});

// ==================== NEGATIVE TEST CASES ====================

// POST - Create Category Negative
it('admin cannot create category', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/categories'), [
            'name' => 'Admin Category',
            'value' => 'admin-category',
            'status' => 'active',
        ]);

    $response->assertStatus(403);
});

it('cannot create category with duplicate value', function () {
    Category::create([
        'name' => 'Existing',
        'value' => 'duplicate-value',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->superadmin, 'api')
        ->postJson(api('/categories'), [
            'name' => 'Duplicate',
            'value' => 'duplicate-value',
            'status' => 'active',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['value']);
});

it('cannot create category with missing required fields', function () {
    $response = $this->actingAs($this->superadmin, 'api')
        ->postJson(api('/categories'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'value', 'status']);
});

it('unauthenticated user cannot create category', function () {
    $response = $this->postJson(api('/categories'), [
        'name' => 'Unauthenticated Category',
        'value' => 'unauthenticated',
        'status' => 'active',
    ]);

    $response->assertStatus(401);
});

// PUT - Update Category Negative
it('admin cannot update category', function () {
    $category = Category::create([
        'name' => 'Category',
        'value' => 'category',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->putJson(api("/categories/{$category->id}"), [
            'name' => 'Updated by Admin',
            'value' => 'updated',
        ]);

    $response->assertStatus(403);
});

it('cannot update non-existent category', function () {
    $response = $this->actingAs($this->superadmin, 'api')
        ->putJson(api('/categories/99999'), [
            'name' => 'Non Existent',
            'value' => 'non-existent',
        ]);

    $response->assertStatus(404);
});

it('cannot update category with duplicate value', function () {
    $category1 = Category::create([
        'name' => 'Category One',
        'value' => 'category-one',
        'status' => 'active',
    ]);
    $category2 = Category::create([
        'name' => 'Category Two',
        'value' => 'category-two',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->superadmin, 'api')
        ->putJson(api("/categories/{$category1->id}"), [
            'name' => 'Updated Name',
            'value' => 'category-two',
            'status' => 'active',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['value']);
});

// DELETE - Delete Category Negative
it('admin cannot delete category', function () {
    $category = Category::create([
        'name' => 'To Delete',
        'value' => 'to-delete',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson(api("/categories/{$category->id}"));

    $response->assertStatus(403);
});

it('cannot delete non-existent category', function () {
    $response = $this->actingAs($this->superadmin, 'api')
        ->deleteJson(api('/categories/99999'));

    $response->assertStatus(404);
});

it('unauthenticated user cannot delete category', function () {
    $category = Category::create([
        'name' => 'To Delete',
        'value' => 'to-delete',
        'status' => 'active',
    ]);

    $response = $this->deleteJson(api("/categories/{$category->id}"));

    $response->assertStatus(401);
});

