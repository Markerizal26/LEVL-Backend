<?php

use Modules\Common\Models\Category;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('category can be created', function () {
    $category = Category::create([
        'name' => 'Technology',
        'value' => 'technology',
        'description' => 'Tech related courses',
    ]);

    assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Technology',
        'value' => 'technology',
    ]);
});

test('category value is unique', function () {
    Category::create(['name' => 'Tech', 'value' => 'tech']);

    expect(fn () => Category::create(['name' => 'Technology', 'value' => 'tech']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});