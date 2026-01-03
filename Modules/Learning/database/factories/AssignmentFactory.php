<?php

namespace Modules\Learning\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Learning\Models\Assignment;
use Modules\Schemes\Models\Lesson;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Learning\Models\Assignment>
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['essay', 'file_upload', 'quiz', 'project']),
            'submission_type' => fake()->randomElement(['text', 'file', 'both']),
            'max_score' => fake()->numberBetween(50, 100),
            'available_from' => now(),
            'deadline_at' => now()->addDays(7),
            'status' => 'published',
            'allow_resubmit' => false,
            'late_penalty_percent' => 0,
        ];
    }

    /**
     * Indicate that the assignment is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the assignment is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    /**
     * Indicate that the assignment allows resubmission.
     */
    public function allowResubmit(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_resubmit' => true,
        ]);
    }

    /**
     * Indicate that the assignment has a late penalty.
     */
    public function withLatePenalty(int $percent = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'late_penalty_percent' => $percent,
        ]);
    }

    /**
     * Indicate that the assignment is past deadline.
     */
    public function pastDeadline(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline_at' => now()->subDays(1),
        ]);
    }

    /**
     * Indicate that the assignment is not yet available.
     */
    public function notYetAvailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_from' => now()->addDays(1),
        ]);
    }
}
