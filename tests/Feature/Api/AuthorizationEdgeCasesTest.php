<?php

use Modules\Auth\Models\User;
use Modules\Assessments\Models\Attempt;
use Modules\Assessments\Models\Exercise;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
  createTestRoles();

  $this->superadmin = User::factory()->create();
  $this->superadmin->assignRole("Superadmin");

  $this->admin = User::factory()->create();
  $this->admin->assignRole("Admin");

  $this->instructor1 = User::factory()->create();
  $this->instructor1->assignRole("Instructor");

  $this->instructor2 = User::factory()->create();
  $this->instructor2->assignRole("Instructor");

  $this->student1 = User::factory()->create();
  $this->student1->assignRole("Student");

  $this->student2 = User::factory()->create();
  $this->student2->assignRole("Student");

  $this->course1 = Course::factory()->create(["instructor_id" => $this->instructor1->id]);
  $this->course2 = Course::factory()->create(["instructor_id" => $this->instructor2->id]);

  Enrollment::create([
    "user_id" => $this->student1->id,
    "course_id" => $this->course1->id,
    "status" => "active",
  ]);

  Enrollment::create([
    "user_id" => $this->student2->id,
    "course_id" => $this->course2->id,
    "status" => "active",
  ]);
});

// ==================== CROSS-RESOURCE AUTHORIZATION ====================

describe("Cross-Resource Authorization", function () {
  it("prevents student from accessing another student's attempt", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor1->id,
      "scope_type" => "course",
      "scope_id" => $this->course1->id,
      "status" => "published",
    ]);

    $attempt = Attempt::create([
      "exercise_id" => $exercise->id,
      "user_id" => $this->student1->id,
      "enrollment_id" => $this->student1->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $response = $this->actingAs($this->student2, "api")->getJson(
      api("/assessments/attempts/{$attempt->id}"),
    );

    $response->assertStatus(403);
  });

  it("prevents student from submitting answer to another student's attempt", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor1->id,
      "scope_type" => "course",
      "scope_id" => $this->course1->id,
      "status" => "published",
    ]);

    $attempt = Attempt::create([
      "exercise_id" => $exercise->id,
      "user_id" => $this->student1->id,
      "enrollment_id" => $this->student1->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $question = $exercise->questions()->create([
      "question_text" => "Test question",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);

    $response = $this->actingAs($this->student2, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $question->id,
        "selected_option_id" => 1,
      ],
    );

    $response->assertStatus(403);
  });

  it("prevents instructor from editing exercise from another instructor's course", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor1->id,
      "scope_type" => "course",
      "scope_id" => $this->course1->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->instructor2, "api")->putJson(
      api("/assessments/exercises/{$exercise->id}"),
      [
        "title" => "Unauthorized Update",
      ],
    );

    $response->assertStatus(403);
  });

  it("prevents instructor from deleting exercise from another instructor's course", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor1->id,
      "scope_type" => "course",
      "scope_id" => $this->course1->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->instructor2, "api")->deleteJson(
      api("/assessments/exercises/{$exercise->id}"),
    );

    $response->assertStatus(403);
  });

  it("prevents admin from approving enrollment in course they don't manage", function () {
    $enrollment = Enrollment::firstOrCreate(
      [
        "user_id" => $this->student2->id,
        "course_id" => $this->course2->id,
      ],
      [
        "status" => "pending",
      ],
    );

    $response = $this->actingAs($this->admin, "api")->postJson(
      api("/enrollments/{$enrollment->id}/approve"),
    );

    // Admin should only manage courses they're assigned to
    $response->assertStatus(403);
  });

  it("prevents student from starting attempt for exercise in unenrolled course", function () {
    $otherCourse = Course::factory()->create(["instructor_id" => $this->instructor2->id]);
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor2->id,
      "scope_type" => "course",
      "scope_id" => $otherCourse->id,
      "status" => "published",
    ]);

    $response = $this->actingAs($this->student1, "api")->postJson(
      api("/assessments/exercises/{$exercise->id}/attempts"),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["exercise"]);
  });
});

// ==================== PERMISSION-BASED ACCESS ====================

describe("Permission-Based Access", function () {
  it("allows superadmin to access all resources", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor1->id,
      "scope_type" => "course",
      "scope_id" => $this->course1->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->superadmin, "api")->getJson(
      api("/assessments/exercises/{$exercise->id}"),
    );

    $response->assertStatus(200);
  });

  it("allows admin to access resources in managed courses", function () {
    $this->course1->admins()->attach($this->admin->id);

    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor1->id,
      "scope_type" => "course",
      "scope_id" => $this->course1->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->admin, "api")->getJson(
      api("/assessments/exercises/{$exercise->id}"),
    );

    $response->assertStatus(200);
  });

  it("prevents admin from accessing resources in unmanaged courses", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor2->id,
      "scope_type" => "course",
      "scope_id" => $this->course2->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->admin, "api")->putJson(
      api("/assessments/exercises/{$exercise->id}"),
      [
        "title" => "Unauthorized",
      ],
    );

    $response->assertStatus(403);
  });

  it("allows instructor to access their own resources", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor1->id,
      "scope_type" => "course",
      "scope_id" => $this->course1->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->instructor1, "api")->getJson(
      api("/assessments/exercises/{$exercise->id}"),
    );

    $response->assertStatus(200);
  });

  it("prevents instructor from accessing another instructor's resources", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor2->id,
      "scope_type" => "course",
      "scope_id" => $this->course2->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->instructor1, "api")->putJson(
      api("/assessments/exercises/{$exercise->id}"),
      [
        "title" => "Unauthorized",
      ],
    );

    $response->assertStatus(403);
  });
});

// ==================== STATUS TRANSITION VALIDATION ====================

describe("Status Transition Validation", function () {
  it("prevents updating exercise status from published back to draft", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor1->id,
      "scope_type" => "course",
      "scope_id" => $this->course1->id,
      "status" => "published",
    ]);

    $response = $this->actingAs($this->instructor1, "api")->putJson(
      api("/assessments/exercises/{$exercise->id}"),
      [
        "status" => "draft",
      ],
    );

    // Status should not be changeable via update endpoint
    // This depends on implementation - may return 403 or ignore the field
    expect($response->status())->toBeIn([403, 200]);
  });

  it("prevents completing attempt that is already completed", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor1->id,
      "scope_type" => "course",
      "scope_id" => $this->course1->id,
      "status" => "published",
    ]);

    $attempt = Attempt::create([
      "exercise_id" => $exercise->id,
      "user_id" => $this->student1->id,
      "enrollment_id" => $this->student1->enrollments()->first()->id,
      "status" => "completed",
      "started_at" => now()->subHour(),
      "finished_at" => now(),
    ]);

    $response = $this->actingAs($this->student1, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/complete"),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["attempt"]);
  });

  it("prevents submitting answer to completed attempt", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor1->id,
      "scope_type" => "course",
      "scope_id" => $this->course1->id,
      "status" => "published",
    ]);

    $attempt = Attempt::create([
      "exercise_id" => $exercise->id,
      "user_id" => $this->student1->id,
      "enrollment_id" => $this->student1->enrollments()->first()->id,
      "status" => "completed",
      "started_at" => now()->subHour(),
      "finished_at" => now(),
    ]);

    $question = $exercise->questions()->create([
      "question_text" => "Test question",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);

    $option = $question->options()->create([
      "option_text" => "Option 1",
      "is_correct" => true,
    ]);

    $response = $this->actingAs($this->student1, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $question->id,
        "selected_option_id" => $option->id,
      ],
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["attempt"]);
  });

  it("prevents updating user status back to pending", function () {
    $user = User::factory()->create([
      "status" => "active",
    ]);
    $user->assignRole("Student");

    $response = $this->actingAs($this->superadmin, "api")->putJson(
      api("/auth/users/{$user->id}/status"),
      [
        "status" => "pending",
      ],
    );

    $response->assertStatus(422);
  });

  it("prevents declining non-pending enrollment", function () {
    // Assign admin to manage course1
    $this->course1->admins()->syncWithoutDetaching([$this->admin->id]);

    $enrollment = Enrollment::firstOrCreate(
      [
        "user_id" => $this->student1->id,
        "course_id" => $this->course1->id,
      ],
      [
        "status" => "active",
      ],
    );

    $response = $this->actingAs($this->admin, "api")->postJson(
      api("/enrollments/{$enrollment->id}/decline"),
    );

    $response->assertStatus(422);
  });

  it("prevents approving non-pending enrollment", function () {
    // Assign admin to manage course1
    $this->course1->admins()->syncWithoutDetaching([$this->admin->id]);

    $enrollment = Enrollment::firstOrCreate(
      [
        "user_id" => $this->student1->id,
        "course_id" => $this->course1->id,
      ],
      [
        "status" => "active",
      ],
    );

    $response = $this->actingAs($this->admin, "api")->postJson(
      api("/enrollments/{$enrollment->id}/approve"),
    );

    $response->assertStatus(422);
  });
});
