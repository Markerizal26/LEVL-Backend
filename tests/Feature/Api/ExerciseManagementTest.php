<?php

use Modules\Auth\Models\User;
use Modules\Assessments\Models\Exercise;
use Modules\Schemes\Models\Course;
use Modules\Enrollments\Models\Enrollment;

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

  $this->course = Course::factory()->create(["instructor_id" => $this->instructor->id]);
  $this->course->admins()->attach($this->admin->id);

  Enrollment::create([
    "user_id" => $this->student->id,
    "course_id" => $this->course->id,
    "status" => "active",
  ]);
});

describe("Exercise Management", function () {
  it("admin can create exercise in managed course", function () {
    $response = $this->actingAs($this->admin, "api")->postJson(api("/assessments/exercises"), [
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "title" => "Midterm Quiz",
      "description" => "First assessment",
      "type" => "quiz",
      "time_limit_minutes" => 30,
      "max_score" => 100,
    ]);

    $response
      ->assertStatus(201)
      ->assertJsonPath("data.exercise.title", "Midterm Quiz")
      ->assertJsonPath("data.exercise.status", "draft");
  });

  it("superadmin can create exercise", function () {
    $response = $this->actingAs($this->superadmin, "api")->postJson(api("/assessments/exercises"), [
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "title" => "Final Exam",
      "description" => "Comprehensive exam",
      "type" => "exam",
      "max_score" => 200,
    ]);

    $response->assertStatus(201);
  });

  it("student cannot create exercise", function () {
    $response = $this->actingAs($this->student, "api")->postJson(api("/assessments/exercises"), [
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "title" => "Malicious Quiz",
      "type" => "quiz",
      "max_score" => 100,
    ]);

    $response->assertStatus(403);
  });

  it("can list exercises with filters", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->admin, "api")->getJson(
      api("/assessments/exercises") . "?scope_type=course&scope_id=" . $this->course->id,
    );

    $response->assertStatus(200)->assertJsonPath("data.0.id", $exercise->id);
  });

  it("can view exercise details", function () {
    $exercise = Exercise::factory()->create(["created_by" => $this->instructor->id]);

    $response = $this->actingAs($this->instructor, "api")->getJson(
      api("/assessments/exercises/{$exercise->id}"),
    );

    $response->assertStatus(200)->assertJsonPath("data.exercise.id", $exercise->id);
  });

  it("can update draft exercise", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/exercises/{$exercise->id}"),
      [
        "title" => "Updated Quiz",
        "max_score" => 150,
      ],
    );

    $response->assertStatus(200)->assertJsonPath("data.exercise.title", "Updated Quiz");
  });

  it("cannot update published exercise", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "published",
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/exercises/{$exercise->id}"),
      [
        "title" => "Tried to Update",
      ],
    );

    $response->assertStatus(403);
  });

  it("can delete draft exercise", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->instructor, "api")->deleteJson(
      api("/assessments/exercises/{$exercise->id}"),
    );

    $response->assertStatus(204);
    $this->assertDatabaseMissing("exercises", ["id" => $exercise->id]);
  });

  it("cannot delete published exercise", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "published",
    ]);

    $response = $this->actingAs($this->instructor, "api")->deleteJson(
      api("/assessments/exercises/{$exercise->id}"),
    );

    $response->assertStatus(403);
  });

  it("can publish exercise with questions", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);
    $exercise->questions()->create([
      "question_text" => "Question 1?",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/exercises/{$exercise->id}/publish"),
    );

    $response->assertStatus(200)->assertJsonPath("data.exercise.status", "published");
  });

  it("cannot publish exercise without questions", function () {
    $exercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "status" => "draft",
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/exercises/{$exercise->id}/publish"),
    );

    $response->assertStatus(422);
  });

  it("can get exercise questions", function () {
    $exercise = Exercise::factory()->create();
    $q1 = $exercise->questions()->create([
      "question_text" => "Q1",
      "type" => "multiple_choice",
      "score_weight" => 5,
    ]);
    $q2 = $exercise->questions()->create([
      "question_text" => "Q2",
      "type" => "free_text",
      "score_weight" => 10,
    ]);

    $response = $this->actingAs($this->instructor, "api")->getJson(
      api("/assessments/exercises/{$exercise->id}/questions"),
    );

    $response->assertStatus(200)->assertJsonCount(2, "data.questions");
  });
});
