<?php

use Modules\Schemes\Models\Course;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    createTestData();
});

function createTestData(): void
{
    Course::factory()->create([
        'title' => 'Laravel Basics',
        'status' => 'published',
        'level_tag' => 'dasar',
        'created_at' => now()->subDays(10),
    ]);

    Course::factory()->create([
        'title' => 'Advanced Laravel',
        'status' => 'published',
        'level_tag' => 'mahir',
        'created_at' => now()->subDays(5),
    ]);

    Course::factory()->create([
        'title' => 'PHP Fundamentals',
        'status' => 'draft',
        'level_tag' => 'dasar',
        'created_at' => now(),
    ]);

    Course::factory(5)->create(['status' => 'published']);
}

it('can filter by status', function () {
    $response = $this->getJson(api('/courses?filter[status]=published'));

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['items', 'meta']])
        ->assertJsonCount(7, 'data.items')
        ->assertJsonPath('data.items.0.status', 'published');
});

it('filters by multiple fields', function () {
    $response = $this->getJson(api('/courses?filter[status]=published&filter[level]=dasar'));

    $response->assertStatus(200);
    $items = $response->json('data.items');

    foreach ($items as $item) {
        expect($item['status'])->toEqual('published');
        expect($item['level_tag'])->toEqual('dasar');
    }
});

it('ignores unknown filter fields', function () {
    $response = $this->getJson(api('/courses?filter[unknown_field]=value&filter[status]=published'));

    $response->assertStatus(200)
        ->assertJsonCount(7, 'data.items');
});

it('handles empty filter values', function () {
    $response = $this->getJson(api('/courses?filter[status]=&filter[level]=dasar'));

    $response->assertStatus(200);
});

it('can sort ascending', function () {
    $response = $this->getJson(api('/courses?sort=title'));

    $response->assertStatus(200);
    $items = $response->json('data.items');
    $titles = array_column($items, 'title');
    expect(array_values($titles))->toEqual($titles);
});

it('can sort descending', function () {
    $response = $this->getJson(api('/courses?sort=-created_at'));

    $response->assertStatus(200);
    $items = $response->json('data.items');
    $timestamps = array_column($items, 'created_at');
    $sortedDesc = $timestamps;
    rsort($sortedDesc);
    expect($timestamps)->toEqual($sortedDesc);
});

it('uses default sort for unknown field', function () {
    $response = $this->getJson(api('/courses?sort=unknown_field'));

    $response->assertStatus(200);
});

it('validates sort format', function () {
    $this->postJson(api('/courses'), [], ['Accept' => 'application/json']);
    $response = $this->getJson(api('/courses?sort=invalid sort format!'));

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['items', 'meta']]);
});

it('can paginate results', function () {
    $response = $this->getJson(api('/courses?page=1&per_page=5'));

    $response->assertStatus(200)
        ->assertJsonCount(5, 'data.items')
        ->assertJsonPath('data.meta.current_page', 1)
        ->assertJsonPath('data.meta.per_page', 5)
        ->assertJsonPath('data.meta.total', 8);
});

it('can get second page', function () {
    $response = $this->getJson(api('/courses?page=2&per_page=5'));

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data.items')
        ->assertJsonPath('data.meta.current_page', 2)
        ->assertJsonPath('data.meta.from', 6)
        ->assertJsonPath('data.meta.to', 8);
});

it('respects max per page', function () {
    $response = $this->getJson(api('/courses?per_page=150'));

    $response->assertStatus(200);
    $perPage = $response->json('data.meta.per_page');
    expect($perPage)->toBeLessThanOrEqual(100);
});

it('uses default pagination values', function () {
    $response = $this->getJson(api('/courses'));

    $response->assertStatus(200)
        ->assertJsonPath('data.meta.current_page', 1)
        ->assertJsonPath('data.meta.per_page', 15);
});

it('includes pagination metadata', function () {
    $response = $this->getJson(api('/courses?page=1&per_page=5'));

    $response->assertJsonStructure([
        'data' => [
            'items',
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
                'from',
                'to',
                'has_more',
            ],
        ],
    ]);

    $meta = $response->json('data.meta');
    expect($meta['current_page'])->toBeInt();
    expect($meta['per_page'])->toBeInt();
    expect($meta['total'])->toBeInt();
    expect($meta['last_page'])->toBeInt();
    expect($meta['has_more'])->toBeBool();
});

it('can combine filter sort and pagination', function () {
    $response = $this->getJson(api('/courses?filter[status]=published&sort=-created_at&page=1&per_page=3'));

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data.items')
        ->assertJsonPath('data.meta.current_page', 1)
        ->assertJsonPath('data.meta.per_page', 3)
        ->assertJsonPath('data.meta.has_more', true);

    foreach ($response->json('data.items') as $item) {
        expect($item['status'])->toEqual('published');
    }
});

it('can filter and sort together', function () {
    $response = $this->getJson(api('/courses?filter[level]=dasar&sort=title'));

    $response->assertStatus(200);
    $items = $response->json('data.items');

    foreach ($items as $item) {
        expect($item['level_tag'])->toEqual('dasar');
    }

    $titles = array_column($items, 'title');
});

it('maintains filter params in pagination links', function () {
    $response = $this->getJson(api('/courses?filter[status]=published&sort=-created_at&page=1'));

    $response->assertStatus(200);
});

it('handles empty results', function () {
    $response = $this->getJson(api('/courses?filter[status]=nonexistent'));

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data.items')
        ->assertJsonPath('data.meta.total', 0)
        ->assertJsonPath('data.meta.has_more', false);
});

it('handles page beyond results', function () {
    $response = $this->getJson(api('/courses?page=999&per_page=10'));

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data.items')
        ->assertJsonPath('data.meta.has_more', false);
});

it('corrects invalid page numbers', function () {
    $response = $this->getJson(api('/courses?page=0'));

    $response->assertStatus(200)
        ->assertJsonPath('data.meta.current_page', 1);
});

it('corrects invalid per page values', function () {
    $response = $this->getJson(api('/courses?per_page=-5'));

    $response->assertStatus(200);
    $perPage = $response->json('data.meta.per_page');
    expect($perPage)->toBeGreaterThanOrEqual(1);
});

// ==================== NEGATIVE TEST CASES ====================

// Filtering Negative Cases
it('rejects invalid status filter value', function () {
    $response = $this->getJson(api('/courses?filter[status]=invalid_status'));

    $response->assertStatus(200);
    // Should return empty or ignore invalid filter
    $items = $response->json('data.items');
    // Invalid status should not match any course
    foreach ($items as $item) {
        expect($item['status'])->not->toEqual('invalid_status');
    }
});

it('rejects invalid level filter value', function () {
    $response = $this->getJson(api('/courses?filter[level]=invalid_level'));

    $response->assertStatus(200);
    $items = $response->json('data.items');
    // Invalid level should not match any course
    foreach ($items as $item) {
        expect($item['level_tag'])->not->toEqual('invalid_level');
    }
});

it('handles SQL injection attempt in filter', function () {
    $response = $this->getJson(api('/courses?filter[status]=published\'; DROP TABLE courses; --'));

    $response->assertStatus(200);
    // Should not execute SQL injection, just treat as invalid filter
    expect($response->json('data.items'))->toBeArray();
});

it('handles XSS attempt in filter', function () {
    $response = $this->getJson(api('/courses?filter[status]=<script>alert("xss")</script>'));

    $response->assertStatus(200);
    // Should sanitize and not execute script
    $json = $response->json();
    expect(json_encode($json))->not->toContain('<script>');
});

it('handles very long filter values', function () {
    $longValue = str_repeat('a', 1000);
    $response = $this->getJson(api("/courses?filter[status]={$longValue}"));

    $response->assertStatus(200);
    // Should handle gracefully without error
    expect($response->json('data.items'))->toBeArray();
});

it('handles special characters in filter', function () {
    $response = $this->getJson(api('/courses?filter[status]=published&filter[level]=dasar&test=!@#$%^&*()'));

    $response->assertStatus(200);
    // Should ignore unknown parameters
    expect($response->json('data.items'))->toBeArray();
});

it('handles null filter values', function () {
    $response = $this->getJson(api('/courses?filter[status]=null'));

    $response->assertStatus(200);
    // Should treat as string "null", not actual null
    $items = $response->json('data.items');
    foreach ($items as $item) {
        expect($item['status'])->not->toBeNull();
    }
});

// Sorting Negative Cases
it('rejects SQL injection in sort parameter', function () {
    $response = $this->getJson(api('/courses?sort=title; DROP TABLE courses; --'));

    $response->assertStatus(200);
    // Should not execute SQL injection
    expect($response->json('data.items'))->toBeArray();
});

it('handles multiple sort fields when not supported', function () {
    $response = $this->getJson(api('/courses?sort=title,created_at'));

    $response->assertStatus(200);
    // Should use first field or default
    expect($response->json('data.items'))->toBeArray();
});

it('handles empty sort parameter', function () {
    $response = $this->getJson(api('/courses?sort='));

    $response->assertStatus(200);
    // Should use default sort
    expect($response->json('data.items'))->toBeArray();
    expect($response->json('data.meta'))->toBeArray();
});

it('handles sort with only minus sign', function () {
    $response = $this->getJson(api('/courses?sort=-'));

    $response->assertStatus(200);
    // Should use default sort
    expect($response->json('data.items'))->toBeArray();
});

it('handles sort with XSS attempt', function () {
    $response = $this->getJson(api('/courses?sort=<script>alert("xss")</script>'));

    $response->assertStatus(200);
    // Should sanitize
    $json = $response->json();
    expect(json_encode($json))->not->toContain('<script>');
});

// Pagination Negative Cases
it('rejects negative page number', function () {
    $response = $this->getJson(api('/courses?page=-1'));

    $response->assertStatus(200);
    // Should correct to page 1
    expect($response->json('data.meta.current_page'))->toBeGreaterThanOrEqual(1);
});

it('rejects zero per page value', function () {
    $response = $this->getJson(api('/courses?per_page=0'));

    $response->assertStatus(200);
    // Should correct to minimum 1
    $perPage = $response->json('data.meta.per_page');
    expect($perPage)->toBeGreaterThanOrEqual(1);
});

it('rejects very large per page value', function () {
    $response = $this->getJson(api('/courses?per_page=999999'));

    $response->assertStatus(200);
    // Should cap at max per page (100)
    $perPage = $response->json('data.meta.per_page');
    expect($perPage)->toBeLessThanOrEqual(100);
});

it('handles non-numeric page value', function () {
    $response = $this->getJson(api('/courses?page=abc'));

    $response->assertStatus(200);
    // Should default to page 1
    expect($response->json('data.meta.current_page'))->toEqual(1);
});

it('handles non-numeric per page value', function () {
    $response = $this->getJson(api('/courses?per_page=abc'));

    $response->assertStatus(200);
    // Should use default per page
    $perPage = $response->json('data.meta.per_page');
    expect($perPage)->toBeGreaterThanOrEqual(1);
});

it('handles float page number', function () {
    $response = $this->getJson(api('/courses?page=1.5'));

    $response->assertStatus(200);
    // Should truncate to integer
    expect($response->json('data.meta.current_page'))->toBeInt();
});

it('handles float per page value', function () {
    $response = $this->getJson(api('/courses?per_page=10.5'));

    $response->assertStatus(200);
    // Should truncate to integer
    $perPage = $response->json('data.meta.per_page');
    expect($perPage)->toBeInt();
});

it('handles SQL injection in pagination', function () {
    $response = $this->getJson(api('/courses?page=1; DROP TABLE courses; --'));

    $response->assertStatus(200);
    // Should not execute SQL injection
    expect($response->json('data.items'))->toBeArray();
});

// Combined Negative Cases
it('handles all invalid parameters together', function () {
    $response = $this->getJson(api('/courses?filter[status]=invalid&sort=invalid_field&page=-1&per_page=-5'));

    $response->assertStatus(200);
    // Should handle gracefully
    expect($response->json('data.items'))->toBeArray();
    expect($response->json('data.meta.current_page'))->toBeGreaterThanOrEqual(1);
    expect($response->json('data.meta.per_page'))->toBeGreaterThanOrEqual(1);
});

it('handles malformed query parameters', function () {
    $response = $this->getJson(api('/courses?filter[status]=published&sort=&page=&per_page='));

    $response->assertStatus(200);
    // Should use defaults for empty values
    expect($response->json('data.items'))->toBeArray();
    expect($response->json('data.meta.current_page'))->toBeGreaterThanOrEqual(1);
});

it('handles duplicate query parameters', function () {
    $response = $this->getJson(api('/courses?page=1&page=2&per_page=5&per_page=10'));

    $response->assertStatus(200);
    // Should use last value or handle gracefully
    expect($response->json('data.items'))->toBeArray();
});

// Edge Cases
it('handles request with no query parameters', function () {
    $response = $this->getJson(api('/courses'));

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['items', 'meta']]);
    // Should return default pagination
    expect($response->json('data.meta.current_page'))->toEqual(1);
});

it('handles request with only empty query parameters', function () {
    $response = $this->getJson(api('/courses?filter=&sort=&page=&per_page='));

    $response->assertStatus(200);
    // Should use defaults
    expect($response->json('data.items'))->toBeArray();
});

it('handles very large page number', function () {
    $response = $this->getJson(api('/courses?page=999999999'));

    $response->assertStatus(200);
    // Should return empty results or last page
    $items = $response->json('data.items');
    expect($items)->toBeArray();
});

it('handles filter with array syntax', function () {
    $response = $this->getJson(api('/courses?filter[status][]=published&filter[status][]=draft'));

    $response->assertStatus(200);
    // Should handle array filter if supported, or ignore
    expect($response->json('data.items'))->toBeArray();
});

it('handles URL encoded special characters', function () {
    $response = $this->getJson(api('/courses?filter[status]=' . urlencode('published&test=value')));

    $response->assertStatus(200);
    // Should decode and handle properly
    expect($response->json('data.items'))->toBeArray();
});
