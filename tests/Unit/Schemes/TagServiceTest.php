<?php

use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Tag;
use Modules\Schemes\Services\TagService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = new TagService();
});

test('create generates slug automatically', function () {
    $tag = $this->service->create(['name' => 'Web Development']);

    expect($tag)->not->toBeNull();
    expect($tag->name)->toEqual('Web Development');
    expect($tag->slug)->toEqual('web-development');
});

test('create handles duplicate names', function () {
    $tag1 = $this->service->create(['name' => 'Backend']);
    $tag2 = $this->service->create(['name' => 'backend']);

    expect($tag2->id)->toEqual($tag1->id);
});

test('create many creates multiple tags', function () {
    $tags = $this->service->createMany(['PHP', 'Laravel', 'JavaScript']);

    expect($tags)->toHaveCount(3);
    expect($tags[0]->name)->toEqual('PHP');
    expect($tags[1]->name)->toEqual('Laravel');
    expect($tags[2]->name)->toEqual('JavaScript');
});

test('create many skips empty names', function () {
    $tags = $this->service->createMany(['PHP', '', '  ', 'Laravel']);

    expect($tags)->toHaveCount(2);
});

test('update changes name and slug', function () {
    $tag = Tag::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

    $updated = $this->service->update($tag->id, ['name' => 'New Name']);

    expect($updated)->not->toBeNull();
    expect($updated->name)->toEqual('New Name');
    expect($updated->slug)->toEqual('new-name');
});

test('update returns null for invalid id', function () {
    $result = $this->service->update(99999, ['name' => 'Test']);

    expect($result)->toBeNull();
});

test('delete removes tag and detaches from courses', function () {
    $tag = Tag::factory()->create();
    $course = Course::factory()->create();
    $course->tags()->attach($tag->id);

    $result = $this->service->delete($tag->id);

    expect($result)->toBeTrue();
    assertDatabaseMissing('tags', ['id' => $tag->id]);
    assertDatabaseMissing('course_tag_pivot', ['tag_id' => $tag->id]);
});

test('sync course tags attaches tags to course', function () {
    $course = Course::factory()->create();
    $tag1 = Tag::factory()->create(['name' => 'PHP']);
    $tag2 = Tag::factory()->create(['name' => 'Laravel']);

    $this->service->syncCourseTags($course, [$tag1->id, $tag2->id]);

    $course->refresh();
    expect($course->tags)->toHaveCount(2);
    expect($course->tags_json)->toContain('PHP');
    expect($course->tags_json)->toContain('Laravel');
});

test('sync course tags creates new tags from names', function () {
    $course = Course::factory()->create();

    $this->service->syncCourseTags($course, ['New Tag', 'Another Tag']);

    $course->refresh();
    expect($course->tags)->toHaveCount(2);
    expect($course->tags->contains('name', 'New Tag'))->toBeTrue();
    expect($course->tags->contains('name', 'Another Tag'))->toBeTrue();
});