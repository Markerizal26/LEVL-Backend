<?php

use Modules\Auth\Models\User;
use Modules\Assessments\Models\Attempt;
use Modules\Assessments\Models\Exercise;
use Modules\Common\Models\Category;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Tag;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
  createTestRoles();

  $this->superadmin = User::factory()->create();
  $this->superadmin->assignRole("Superadmin");

  $this->admin = User::factory()->create();
  $this->admin->assignRole("Admin");

  $this->instructor = User::factory()->create();
  $this->instructor->assignRole("Instructor");

  $this->student = User::factory()->create();
  $this->student->assignRole("Student");

  $this->category = Category::factory()->create();
  $this->course = Course::factory()->create(["instructor_id" => $this->instructor->id]);
});

// ==================== USERS PAGINATION & FILTERING ====================

describe("Users List Pagination & Filtering", function () {
  beforeEach(function () {
    // Create multiple users with different statuses and roles
    User::factory()
      ->count(5)
      ->create(["status" => "active"])
      ->each(function ($user, $index) {
        $user->email = "active{$index}@example.com";
        $user->username = "activeuser{$index}";
        $user->save();
        $user->assignRole("Student");
      });
    User::factory()
      ->count(3)
      ->create(["status" => "inactive"])
      ->each(function ($user, $index) {
        $user->email = "inactive{$index}@example.com";
        $user->username = "inactiveuser{$index}";
        $user->save();
        $user->assignRole("Student");
      });
    User::factory()
      ->count(2)
      ->create(["status" => "pending"])
      ->each(function ($user, $index) {
        $user->email = "pending{$index}@example.com";
        $user->username = "pendinguser{$index}";
        $user->save();
        $user->assignRole("Student");
      });
  });

  it("paginates users list", function () {
    $response = $this->actingAs($this->superadmin, "api")->getJson(api("/auth/users?per_page=5"));

    $response
      ->assertStatus(200)
      ->assertJsonStructure([
        "data",
        "meta" => [
          "pagination" => ["current_page", "per_page", "total", "last_page", "from", "to"],
        ],
      ])
      ->assertJsonCount(5, "data")
      ->assertJsonPath("meta.pagination.per_page", 5);
  });

  it("filters users by status", function () {
    $response = $this->actingAs($this->superadmin, "api")->getJson(
      api("/auth/users?filter[status]=active"),
    );

    $response->assertStatus(200);
    $items = $response->json("data");
    foreach ($items as $item) {
      expect($item["status"])->toBe("active");
    }
  });

  it("filters users by email", function () {
    $user = User::factory()->create(["email" => "filtertest@example.com"]);
    $user->assignRole("Student");

    $response = $this->actingAs($this->superadmin, "api")->getJson(
      api("/auth/users?filter[email]=filtertest"),
    );

    $response->assertStatus(200);
    $items = $response->json("data");
    expect($items)->not()->toBeEmpty();
    expect($items[0]["email"])->toContain("filtertest");
  });

  it("sorts users by name ascending", function () {
    $response = $this->actingAs($this->superadmin, "api")->getJson(api("/auth/users?sort=name"));

    $response->assertStatus(200);
    $items = $response->json("data");
    $names = array_column($items, "name");
    expect($names)->toEqual(array_values($names));
  });

  it("sorts users by created_at descending", function () {
    $response = $this->actingAs($this->superadmin, "api")->getJson(
      api("/auth/users?sort=-created_at"),
    );

    $response->assertStatus(200);
    $items = $response->json("data");
    $timestamps = array_column($items, "created_at");
    $sortedDesc = $timestamps;
    rsort($sortedDesc);
    expect($timestamps)->toEqual($sortedDesc);
  });

  it("combines filtering, sorting, and pagination", function () {
    $response = $this->actingAs($this->superadmin, "api")->getJson(
      api("/auth/users?filter[status]=active&sort=-created_at&per_page=3&page=1"),
    );

    $response
      ->assertStatus(200)
      ->assertJsonCount(3, "data")
      ->assertJsonPath("meta.pagination.per_page", 3)
      ->assertJsonPath("meta.pagination.current_page", 1);
  });
});

// ==================== EXERCISES PAGINATION & FILTERING ====================

describe("Exercises List Pagination & Filtering", function () {
  beforeEach(function () {
    Exercise::factory()
      ->count(5)
      ->create([
        "created_by" => $this->instructor->id,
        "scope_type" => "course",
        "scope_id" => $this->course->id,
        "status" => "published",
      ])
      ->each(function ($exercise, $index) {
        $exercise->title = "Published Exercise {$index}";
        $exercise->save();
      });
    Exercise::factory()
      ->count(3)
      ->create([
        "created_by" => $this->instructor->id,
        "scope_type" => "course",
        "scope_id" => $this->course->id,
        "status" => "draft",
      ])
      ->each(function ($exercise, $index) {
        $exercise->title = "Draft Exercise {$index}";
        $exercise->save();
      });
  });

  it("paginates exercises list", function () {
    $response = $this->actingAs($this->admin, "api")->getJson(
      api("/assessments/exercises?per_page=5"),
    );

    $response
      ->assertStatus(200)
      ->assertJsonStructure([
        "data",
        "meta" => [
          "pagination" => ["current_page", "per_page", "total", "last_page"],
        ],
      ])
      ->assertJsonCount(5, "data");
  });

  it("filters exercises by status", function () {
    $response = $this->actingAs($this->admin, "api")->getJson(
      api("/assessments/exercises?filter[status]=published"),
    );

    $response->assertStatus(200);
    $items = $response->json("data");
    foreach ($items as $item) {
      expect($item["status"])->toBe("published");
    }
  });

  it("filters exercises by type", function () {
    Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "type" => "exam",
      "status" => "published",
    ]);

    $response = $this->actingAs($this->admin, "api")->getJson(
      api("/assessments/exercises?filter[type]=exam"),
    );

    $response->assertStatus(200);
    $items = $response->json("data");
    foreach ($items as $item) {
      expect($item["type"])->toBe("exam");
    }
  });

  it("sorts exercises by title", function () {
    $response = $this->actingAs($this->admin, "api")->getJson(
      api("/assessments/exercises?sort=title"),
    );

    $response->assertStatus(200);
    $items = $response->json("data");
    $titles = array_column($items, "title");
    expect($titles)->toEqual(array_values($titles));
  });

  it("combines filtering, sorting, and pagination for exercises", function () {
    $response = $this->actingAs($this->admin, "api")->getJson(
      api(
        "/assessments/exercises?filter[status]=published&filter[scope_type]=course&sort=-created_at&per_page=3",
      ),
    );

    $response
      ->assertStatus(200)
      ->assertJsonCount(3, "data")
      ->assertJsonPath("meta.pagination.per_page", 3);
  });
});

// ==================== ATTEMPTS PAGINATION & FILTERING ====================

describe("Attempts List Pagination & Filtering", function () {
  beforeEach(function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "status" => "published",
    ]);

    $enrollment = Enrollment::create([
      "user_id" => $this->student->id,
      "course_id" => $this->course->id,
      "status" => "active",
    ]);

    for ($i = 0; $i < 5; $i++) {
      Attempt::create([
        "exercise_id" => $exercise->id,
        "user_id" => $this->student->id,
        "enrollment_id" => $enrollment->id,
        "status" => "completed",
        "started_at" => now()->subHours($i + 1),
        "finished_at" => now()->subHours($i),
        "total_questions" => 5,
      ]);
    }
    for ($i = 0; $i < 3; $i++) {
      Attempt::create([
        "exercise_id" => $exercise->id,
        "user_id" => $this->student->id,
        "enrollment_id" => $enrollment->id,
        "status" => "in_progress",
        "started_at" => now()->subMinutes($i * 10),
        "total_questions" => 5,
      ]);
    }
  });

  it("paginates attempts list", function () {
    $response = $this->actingAs($this->student, "api")->getJson(
      api("/assessments/attempts?per_page=5"),
    );

    $response
      ->assertStatus(200)
      ->assertJsonStructure([
        "data",
        "meta" => [
          "pagination" => ["current_page", "per_page", "total", "last_page"],
        ],
      ])
      ->assertJsonCount(5, "data");
  });

  it("filters attempts by status", function () {
    $response = $this->actingAs($this->student, "api")->getJson(
      api("/assessments/attempts?filter[status]=completed"),
    );

    $response->assertStatus(200);
    $items = $response->json("data");
    foreach ($items as $item) {
      expect($item["status"])->toBe("completed");
    }
  });

  it("sorts attempts by started_at descending", function () {
    $response = $this->actingAs($this->student, "api")->getJson(
      api("/assessments/attempts?sort=-started_at"),
    );

    $response->assertStatus(200);
    $items = $response->json("data");
    $timestamps = array_column($items, "started_at");
    $sortedDesc = $timestamps;
    rsort($sortedDesc);
    expect($timestamps)->toEqual($sortedDesc);
  });
});

// ==================== COURSES PAGINATION & FILTERING ====================

describe("Courses List Pagination & Filtering", function () {
  beforeEach(function () {
    Course::factory()
      ->count(10)
      ->create(["status" => "published"])
      ->each(function ($course, $index) {
        $course->code = "PUB-{$index}";
        $course->title = "Published Course {$index}";
        $course->save();
      });
    Course::factory()
      ->count(5)
      ->create(["status" => "draft"])
      ->each(function ($course, $index) {
        $course->code = "DRAFT-{$index}";
        $course->title = "Draft Course {$index}";
        $course->save();
      });
  });

  it("paginates courses list", function () {
    $response = $this->getJson(api("/courses?per_page=5"));

    $response
      ->assertStatus(200)
      ->assertJsonStructure([
        "data",
        "meta" => [
          "pagination" => ["current_page", "per_page", "total", "last_page"],
        ],
      ])
      ->assertJsonCount(5, "data");
  });

  it("filters courses by status", function () {
    $response = $this->getJson(api("/courses?filter[status]=published"));

    $response->assertStatus(200);
    $items = $response->json("data");
    foreach ($items as $item) {
      expect($item["status"])->toBe("published");
    }
  });

  it("filters courses by level_tag", function () {
    Course::factory()->create([
      "level_tag" => "mahir",
      "status" => "published",
    ]);

    $response = $this->getJson(api("/courses?filter[level_tag]=mahir"));

    $response->assertStatus(200);
    $items = $response->json("data");
    foreach ($items as $item) {
      expect($item["level_tag"])->toBe("mahir");
    }
  });

  it("sorts courses by title", function () {
    $response = $this->getJson(api("/courses?sort=title"));

    $response->assertStatus(200);
    $items = $response->json("data");
    $titles = array_column($items, "title");
    expect($titles)->toEqual(array_values($titles));
  });

  it("combines filtering, sorting, and pagination for courses", function () {
    $response = $this->getJson(
      api("/courses?filter[status]=published&sort=-created_at&per_page=3&page=1"),
    );

    $response
      ->assertStatus(200)
      ->assertJsonCount(3, "data")
      ->assertJsonPath("meta.pagination.per_page", 3)
      ->assertJsonPath("meta.pagination.current_page", 1);
  });
});

// ==================== CATEGORIES PAGINATION & FILTERING ====================

describe("Categories List Pagination & Filtering", function () {
  beforeEach(function () {
    Category::factory()
      ->count(10)
      ->create(["status" => "active"])
      ->each(function ($category, $index) {
        $category->name = "Active Category {$index}";
        $category->value = "active-category-{$index}";
        $category->save();
      });
    Category::factory()
      ->count(5)
      ->create(["status" => "inactive"])
      ->each(function ($category, $index) {
        $category->name = "Inactive Category {$index}";
        $category->value = "inactive-category-{$index}";
        $category->save();
      });
  });

  it("paginates categories list", function () {
    $response = $this->actingAs($this->superadmin, "api")->getJson(api("/categories?per_page=5"));

    $response
      ->assertStatus(200)
      ->assertJsonStructure([
        "data",
        "meta" => [
          "pagination" => ["current_page", "per_page", "total", "last_page"],
        ],
      ])
      ->assertJsonCount(5, "data");
  });

  it("filters categories by status", function () {
    $response = $this->actingAs($this->superadmin, "api")->getJson(
      api("/categories?filter[status]=active"),
    );

    $response->assertStatus(200);
    $items = $response->json("data");
    foreach ($items as $item) {
      expect($item["status"])->toBe("active");
    }
  });

  it("sorts categories by name", function () {
    $response = $this->actingAs($this->superadmin, "api")->getJson(api("/categories?sort=name"));

    $response->assertStatus(200);
    $items = $response->json("data");
    $names = array_column($items, "name");
    expect($names)->toEqual(array_values($names));
  });
});

// ==================== TAGS PAGINATION & FILTERING ====================

describe("Tags List Pagination & Filtering", function () {
  beforeEach(function () {
    for ($i = 0; $i < 15; $i++) {
      $uniqueName = "Test Tag {$i} " . uniqid();
      Tag::create([
        "name" => $uniqueName,
        "slug" => \Illuminate\Support\Str::slug($uniqueName),
      ]);
    }
  });

  it("paginates tags list", function () {
    $response = $this->actingAs($this->admin, "api")->getJson(api("/course-tags?per_page=10"));

    $response
      ->assertStatus(200)
      ->assertJsonStructure([
        "data",
        "meta" => [
          "pagination" => ["current_page", "per_page", "total", "last_page"],
        ],
      ])
      ->assertJsonCount(10, "data");
  });

  it("sorts tags by name", function () {
    $response = $this->actingAs($this->admin, "api")->getJson(api("/course-tags?sort=name"));

    $response->assertStatus(200);
    $items = $response->json("data");
    $names = array_column($items, "name");
    expect($names)->toEqual(array_values($names));
  });
});
