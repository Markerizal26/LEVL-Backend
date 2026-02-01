<?php

namespace Modules\Gamification\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Gamification\Models\Point;


class PointFactory extends Factory
{
    protected $model = Point::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source_type' => fake()->randomElement(['lesson_completion', 'assignment_submission', 'course_completion']),
            'source_id' => fake()->numberBetween(1, 100),
            'points' => fake()->numberBetween(10, 100),
            'reason' => fake()->randomElement(['lesson_completed', 'assignment_submitted', 'course_completed']),
            'description' => fake()->sentence(),
        ];
    }

    public function lessonCompletion(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'lesson_completion',
            'reason' => 'lesson_completed',
            'points' => 10,
        ]);
    }

    public function assignmentSubmission(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'assignment_submission',
            'reason' => 'assignment_submitted',
            'points' => 50,
        ]);
    }

    public function courseCompletion(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'course_completion',
            'reason' => 'course_completed',
            'points' => 100,
        ]);
    }
}
