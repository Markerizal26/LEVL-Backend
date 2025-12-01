<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Enrollments\Models\CourseProgress;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Enrollments\Models\CourseProgress>
 */
class CourseProgressFactory extends Factory
{
  protected $model = CourseProgress::class;

  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      "enrollment_id" => null,
      "status" => fake()->randomElement(["not_started", "in_progress", "completed"]),
      "progress_percent" => fake()->randomFloat(2, 0, 100),
      "started_at" => now(),
      "completed_at" => null,
    ];
  }

  /**
   * Indicate that the course progress is completed.
   */
  public function completed(): static
  {
    return $this->state(
      fn(array $attributes) => [
        "status" => "completed",
        "progress_percent" => 100,
        "completed_at" => now(),
      ],
    );
  }

  /**
   * Indicate that the course progress is in progress.
   */
  public function inProgress(): static
  {
    return $this->state(
      fn(array $attributes) => [
        "status" => "in_progress",
        "progress_percent" => fake()->randomFloat(2, 1, 99),
      ],
    );
  }
}
