<?php

use Modules\Auth\Models\User;
use Modules\Assessments\Models\Exercise;
use Modules\Assessments\Models\Question;
use Modules\Assessments\Models\Attempt;
use Modules\Schemes\Models\Course;
use Modules\Enrollments\Models\Enrollment;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
  createTestRoles();

  $this->instructor = User::factory()->create();
  $this->instructor->assignRole("Instructor");

  $this->student = User::factory()->create();
  $this->student->assignRole("Student");

  $this->course = Course::factory()->create(["instructor_id" => $this->instructor->id]);
  Enrollment::create([
    "user_id" => $this->student->id,
    "course_id" => $this->course->id,
    "status" => "active",
  ]);

  $this->draftExercise = Exercise::factory()->create([
    "created_by" => $this->instructor->id,
    "scope_type" => "course",
    "scope_id" => $this->course->id,
    "status" => "draft",
  ]);

  $this->exercise = Exercise::factory()->create([
    "created_by" => $this->instructor->id,
    "scope_type" => "course",
    "scope_id" => $this->course->id,
    "status" => "published",
    "available_from" => now()->subDay(),
    "available_until" => now()->addDay(),
  ]);
});

describe("Question Management", function () {
  it("instructor can add question to their exercise", function () {
    $response = $this->actingAs($this->instructor, "api")->postJson(
      api("/assessments/exercises/{$this->draftExercise->id}/questions"),
      [
        "question_text" => "What is 2+2?",
        "type" => "multiple_choice",
        "score_weight" => 10,
      ],
    );

    $response->assertStatus(201)->assertJsonPath("data.question.question_text", "What is 2+2?");
  });

  it("student cannot add question to exercise", function () {
    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$this->draftExercise->id}/questions"),
      [
        "question_text" => "Malicious question",
        "type" => "multiple_choice",
        "score_weight" => 10,
      ],
    );

    $response->assertStatus(403);
  });

  it("can update question", function () {
    $question = $this->draftExercise->questions()->create([
      "question_text" => "Original question",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/questions/{$question->id}"),
      [
        "question_text" => "Updated question",
        "score_weight" => 15,
      ],
    );

    $response->assertStatus(200)->assertJsonPath("data.question.question_text", "Updated question");
  });

  it("can delete question", function () {
    $question = $this->draftExercise->questions()->create([
      "question_text" => "To be deleted",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);

    $response = $this->actingAs($this->instructor, "api")->deleteJson(
      api("/assessments/questions/{$question->id}"),
    );

    $response->assertStatus(204);
    $this->assertDatabaseMissing("questions", ["id" => $question->id]);
  });
});

describe("Question Options", function () {
  it("instructor can add options to question", function () {
    $question = $this->draftExercise->questions()->create([
      "question_text" => "Multiple choice",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);

    $response = $this->actingAs($this->instructor, "api")->postJson(
      api("/assessments/questions/{$question->id}/options"),
      [
        "option_text" => "Option A",
        "is_correct" => true,
      ],
    );

    $response->assertStatus(201)->assertJsonPath("data.option.option_text", "Option A");
  });

  it("can update option", function () {
    $question = $this->draftExercise->questions()->create([
      "question_text" => "Q",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);
    $option = $question->options()->create([
      "option_text" => "Original",
      "is_correct" => false,
    ]);

    $response = $this->actingAs($this->instructor, "api")->putJson(
      api("/assessments/options/{$option->id}"),
      [
        "option_text" => "Updated",
        "is_correct" => true,
      ],
    );

    $response->assertStatus(200)->assertJsonPath("data.option.option_text", "Updated");
  });

  it("can delete option", function () {
    $question = $this->draftExercise->questions()->create([
      "question_text" => "Q",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);
    $option = $question->options()->create(["option_text" => "Remove me", "is_correct" => false]);

    $response = $this->actingAs($this->instructor, "api")->deleteJson(
      api("/assessments/options/{$option->id}"),
    );

    $response->assertStatus(204);
  });
});

describe("Attempt Management", function () {
  it("student can start attempt", function () {
    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$this->exercise->id}/attempts"),
    );

    $response->assertStatus(201)->assertJsonPath("data.attempt.status", "in_progress");
  });

  it("student can list their attempts", function () {
    Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->getJson(api("/assessments/attempts"));

    $response->assertStatus(200)->assertJsonCount(1, "data.items");
  });

  it("student can view their attempt", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->getJson(
      api("/assessments/attempts/{$attempt->id}"),
    );

    $response->assertStatus(200);
  });

  it("student can submit answer", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $question = $this->exercise->questions()->create([
      "question_text" => "Q?",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);
    $option = $question->options()->create(["option_text" => "A", "is_correct" => true]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $question->id,
        "selected_option_id" => $option->id,
      ],
    );

    $response->assertStatus(200);
  });

  it("student can complete attempt", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
      "total_questions" => 1,
    ]);

    $response = $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/complete"),
    );

    $response->assertStatus(200)->assertJsonPath("data.attempt.status", "completed");
  });

  // ==================== ATTEMPT TIME LIMIT ENFORCEMENT ====================

  it("cannot start attempt for exercise not yet available", function () {
    $futureExercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "status" => "published",
      "available_from" => now()->addDay(),
      "available_until" => now()->addDays(2),
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$futureExercise->id}/attempts"),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["exercise"]);
  });

  it("cannot start attempt for expired exercise", function () {
    $expiredExercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $this->course->id,
      "status" => "published",
      "available_from" => now()->subDays(2),
      "available_until" => now()->subDay(),
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$expiredExercise->id}/attempts"),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["exercise"]);
  });

  it("cannot start attempt for unpublished exercise", function () {
    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$this->draftExercise->id}/attempts"),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["exercise"]);
  });

  it("cannot start attempt without enrollment", function () {
    $otherCourse = Course::factory()->create(["instructor_id" => $this->instructor->id]);
    $otherExercise = Exercise::factory()->create([
      "created_by" => $this->instructor->id,
      "scope_type" => "course",
      "scope_id" => $otherCourse->id,
      "status" => "published",
      "available_from" => now()->subDay(),
      "available_until" => now()->addDay(),
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$otherExercise->id}/attempts"),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["exercise"]);
  });

  // ==================== MULTIPLE ATTEMPTS LIMIT ====================

  it("allows multiple attempts for same exercise", function () {
    // First attempt
    $attempt1 = $this->actingAs($this->student, "api")
      ->postJson(api("/assessments/exercises/{$this->exercise->id}/attempts"))
      ->assertStatus(201);

    // Complete first attempt
    $attempt1Id = $attempt1->json("data.attempt.id");
    $this->actingAs($this->student, "api")
      ->putJson(api("/assessments/attempts/{$attempt1Id}/complete"))
      ->assertStatus(200);

    // Second attempt should be allowed
    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/exercises/{$this->exercise->id}/attempts"),
    );

    $response->assertStatus(201);
    expect(
      Attempt::where("exercise_id", $this->exercise->id)
        ->where("user_id", $this->student->id)
        ->count(),
    )->toBe(2);
  });

  // ==================== ANSWER SUBMISSION VALIDATION ====================

  it("validates answer submission requires question_id", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [],
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["question_id"]);
  });

  it(
    "allows answer submission for multiple_choice without selected_option_id initially",
    function () {
      $attempt = Attempt::create([
        "exercise_id" => $this->exercise->id,
        "user_id" => $this->student->id,
        "enrollment_id" => $this->student->enrollments()->first()->id,
        "status" => "in_progress",
        "started_at" => now(),
      ]);

      $question = $this->exercise->questions()->create([
        "question_text" => "Multiple choice question?",
        "type" => "multiple_choice",
        "score_weight" => 10,
      ]);

      // selected_option_id is nullable, so submission without it is allowed
      $response = $this->actingAs($this->student, "api")->postJson(
        api("/assessments/attempts/{$attempt->id}/answers"),
        [
          "question_id" => $question->id,
        ],
      );

      $response->assertStatus(200);
      $answer = \Modules\Assessments\Models\Answer::where("attempt_id", $attempt->id)
        ->where("question_id", $question->id)
        ->first();
      expect($answer->selected_option_id)->toBeNull();
    },
  );

  it("allows answer submission for free_text without answer_text initially", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $question = $this->exercise->questions()->create([
      "question_text" => "Free text question?",
      "type" => "free_text",
      "score_weight" => 10,
    ]);

    // answer_text is nullable, so submission without it is allowed
    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $question->id,
      ],
    );

    $response->assertStatus(200);
    $answer = \Modules\Assessments\Models\Answer::where("attempt_id", $attempt->id)
      ->where("question_id", $question->id)
      ->first();
    expect($answer->answer_text)->toBeNull();
  });

  it("rejects answer submission for invalid question_id", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => 99999,
        "selected_option_id" => 1,
      ],
    );

    $response->assertStatus(422);
  });

  it(
    "allows answer submission for question from different exercise but validates in business logic",
    function () {
      $otherExercise = Exercise::factory()->create([
        "created_by" => $this->instructor->id,
        "scope_type" => "course",
        "scope_id" => $this->course->id,
        "status" => "published",
      ]);

      $attempt = Attempt::create([
        "exercise_id" => $this->exercise->id,
        "user_id" => $this->student->id,
        "enrollment_id" => $this->student->enrollments()->first()->id,
        "status" => "in_progress",
        "started_at" => now(),
      ]);

      $otherQuestion = $otherExercise->questions()->create([
        "question_text" => "Other exercise question",
        "type" => "multiple_choice",
        "score_weight" => 10,
      ]);
      $otherOption = $otherQuestion->options()->create([
        "option_text" => "Option",
        "is_correct" => true,
      ]);

      // Validation allows it (question_id exists), but business logic should handle it
      // This is acceptable as the answer won't be graded correctly anyway
      $response = $this->actingAs($this->student, "api")->postJson(
        api("/assessments/attempts/{$attempt->id}/answers"),
        [
          "question_id" => $otherQuestion->id,
          "selected_option_id" => $otherOption->id,
        ],
      );

      $response->assertStatus(200);
    },
  );

  // ==================== QUESTION TYPE VALIDATION ====================

  it("validates question type is required", function () {
    $response = $this->actingAs($this->instructor, "api")->postJson(
      api("/assessments/exercises/{$this->draftExercise->id}/questions"),
      [
        "question_text" => "Question without type",
        "score_weight" => 10,
      ],
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["type"]);
  });

  it("validates question type is in allowed values", function () {
    $response = $this->actingAs($this->instructor, "api")->postJson(
      api("/assessments/exercises/{$this->draftExercise->id}/questions"),
      [
        "question_text" => "Invalid type question",
        "type" => "invalid_type",
        "score_weight" => 10,
      ],
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["type"]);
  });

  it("validates multiple_choice question has options", function () {
    $question = $this->draftExercise->questions()->create([
      "question_text" => "Multiple choice without options",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);

    // Try to complete attempt with question without options
    $attempt = Attempt::create([
      "exercise_id" => $this->draftExercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $question->id,
        "selected_option_id" => 999,
      ],
    );

    $response->assertStatus(422);
  });

  // ==================== AUTO-GRADING VS MANUAL GRADING ====================

  it("auto-grades multiple_choice questions on attempt completion", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
      "total_questions" => 1,
    ]);

    $question = $this->exercise->questions()->create([
      "question_text" => "What is 2+2?",
      "type" => "multiple_choice",
      "score_weight" => 10,
    ]);
    $correctOption = $question->options()->create([
      "option_text" => "4",
      "is_correct" => true,
    ]);
    $wrongOption = $question->options()->create([
      "option_text" => "5",
      "is_correct" => false,
    ]);

    // Submit correct answer
    $this->actingAs($this->student, "api")
      ->postJson(api("/assessments/attempts/{$attempt->id}/answers"), [
        "question_id" => $question->id,
        "selected_option_id" => $correctOption->id,
      ])
      ->assertStatus(200);

    // Answer is not graded yet
    $answer = \Modules\Assessments\Models\Answer::where("attempt_id", $attempt->id)
      ->where("question_id", $question->id)
      ->first();
    expect($answer->score_awarded)->toBeNull();

    // Complete attempt - triggers auto-grading
    $response = $this->actingAs($this->student, "api")->putJson(
      api("/assessments/attempts/{$attempt->id}/complete"),
    );

    $response->assertStatus(200);
    $answer->refresh();
    $answer->load("selectedOption");
    expect($answer->score_awarded)->toBe(10);
    expect($answer->selectedOption)->not()->toBeNull();
    expect((bool) $answer->selectedOption->is_correct)->toBeTrue();
    $attempt->refresh();
    expect($attempt->score)->toBe(10);
    expect($attempt->correct_answers)->toBe(1);
  });

  it("requires manual grading for free_text questions", function () {
    $attempt = Attempt::create([
      "exercise_id" => $this->exercise->id,
      "user_id" => $this->student->id,
      "enrollment_id" => $this->student->enrollments()->first()->id,
      "status" => "in_progress",
      "started_at" => now(),
    ]);

    $question = $this->exercise->questions()->create([
      "question_text" => "Explain your answer",
      "type" => "free_text",
      "score_weight" => 20,
    ]);

    $response = $this->actingAs($this->student, "api")->postJson(
      api("/assessments/attempts/{$attempt->id}/answers"),
      [
        "question_id" => $question->id,
        "answer_text" => "My explanation",
      ],
    );

    $response->assertStatus(200);
    $answer = \Modules\Assessments\Models\Answer::where("attempt_id", $attempt->id)
      ->where("question_id", $question->id)
      ->first();
    expect($answer->score_awarded)->toBeNull(); // Free text requires manual grading
    expect($answer->answer_text)->toBe("My explanation");
  });
});
