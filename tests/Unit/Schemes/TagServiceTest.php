<?php

use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Tag;
use Modules\Schemes\Services\TagService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function getTagService(): TagService
{
  return app(TagService::class);
}

test("create generates slug automatically", function () {
  $service = getTagService();
  $tag = $service->create(["name" => "Web Development"]);

  expect($tag)->not->toBeNull();
  expect($tag->name)->toEqual("Web Development");
  expect($tag->slug)->toEqual("web-development");
});

test("create handles duplicate names", function () {
  $service = getTagService();
  $tag1 = $service->create(["name" => "Backend"]);
  $tag2 = $service->create(["name" => "backend"]);

  expect($tag2->id)->toEqual($tag1->id);
});

test("create many creates multiple tags", function () {
  $service = getTagService();
  $tags = $service->createMany(["PHP", "Laravel", "JavaScript"]);

  expect($tags)->toHaveCount(3);
  expect($tags[0]->name)->toEqual("PHP");
  expect($tags[1]->name)->toEqual("Laravel");
  expect($tags[2]->name)->toEqual("JavaScript");
});

test("create many skips empty names", function () {
  $service = getTagService();
  $tags = $service->createMany(["PHP", "", "  ", "Laravel"]);

  expect($tags)->toHaveCount(2);
});

test("update changes name and slug", function () {
  $service = getTagService();
  $tag = Tag::factory()->create(["name" => "Old Name", "slug" => "old-name"]);

  $updated = $service->update($tag->id, ["name" => "New Name"]);

  expect($updated)->not->toBeNull();
  expect($updated->name)->toEqual("New Name");
  expect($updated->slug)->toEqual("new-name");
});

test("update returns null for invalid id", function () {
  $service = getTagService();
  $result = $service->update(99999, ["name" => "Test"]);

  expect($result)->toBeNull();
});

test("delete removes tag and detaches from courses", function () {
  $service = getTagService();
  $tag = Tag::factory()->create();
  $course = Course::factory()->create();
  $course->tags()->attach($tag->id);

  $result = $service->delete($tag->id);

  expect($result)->toBeTrue();
  assertDatabaseMissing("tags", ["id" => $tag->id]);
  assertDatabaseMissing("course_tag_pivot", ["tag_id" => $tag->id]);
});

test("sync course tags attaches tags to course", function () {
  $service = getTagService();
  $course = Course::factory()->create();
  $tag1 = $service->create(["name" => "PHP-Test-" . uniqid()]);
  $tag2 = $service->create(["name" => "Laravel-Test-" . uniqid()]);

  $service->syncCourseTags($course, [$tag1->id, $tag2->id]);

  $course->refresh();
  expect($course->tags)->toHaveCount(2);
  expect($course->tags_json)->toContain($tag1->name);
  expect($course->tags_json)->toContain($tag2->name);
});

test("sync course tags creates new tags from names", function () {
  $service = getTagService();
  $course = Course::factory()->create();

  $service->syncCourseTags($course, ["New Tag", "Another Tag"]);

  $course->refresh();
  expect($course->tags)->toHaveCount(2);
  expect($course->tags->contains("name", "New Tag"))->toBeTrue();
  expect($course->tags->contains("name", "Another Tag"))->toBeTrue();
});
