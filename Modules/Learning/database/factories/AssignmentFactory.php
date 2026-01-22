<?php

namespace Modules\Learning\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Learning\Enums\RandomizationType;
use Modules\Learning\Enums\ReviewMode;
use Modules\Learning\Models\Assignment;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;

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
            'assignable_type' => null,
            'assignable_id' => null,
            'created_by' => User::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['essay', 'file_upload', 'quiz', 'project']),
            'submission_type' => fake()->randomElement(['text', 'file', 'mixed']),
            'max_score' => fake()->numberBetween(50, 100),
            'available_from' => now(),
            'deadline_at' => now()->addDays(7),
            'tolerance_minutes' => 0,
            'max_attempts' => null,
            'cooldown_minutes' => 0,
            'retake_enabled' => false,
            'review_mode' => ReviewMode::Immediate,
            'randomization_type' => RandomizationType::Static,
            'question_bank_count' => null,
            'status' => 'published',
            'allow_resubmit' => false,
            'late_penalty_percent' => 0,
        ];
    }

    /**
     * Attach to a lesson using polymorphic relationship.
     */
    public function forLesson(?Lesson $lesson = null): static
    {
        return $this->state(fn (array $attributes) => [
            'lesson_id' => null,
            'assignable_type' => Lesson::class,
            'assignable_id' => $lesson?->id ?? Lesson::factory(),
        ]);
    }

    /**
     * Attach to a unit using polymorphic relationship.
     */
    public function forUnit(?Unit $unit = null): static
    {
        return $this->state(fn (array $attributes) => [
            'lesson_id' => null,
            'assignable_type' => Unit::class,
            'assignable_id' => $unit?->id ?? Unit::factory(),
        ]);
    }

    /**
     * Attach to a course using polymorphic relationship.
     */
    public function forCourse(?Course $course = null): static
    {
        return $this->state(fn (array $attributes) => [
            'lesson_id' => null,
            'assignable_type' => Course::class,
            'assignable_id' => $course?->id ?? Course::factory(),
        ]);
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

    /**
     * Set deadline tolerance in minutes.
     */
    public function withTolerance(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'tolerance_minutes' => $minutes,
        ]);
    }

    /**
     * Set maximum attempts.
     */
    public function withMaxAttempts(int $attempts): static
    {
        return $this->state(fn (array $attributes) => [
            'max_attempts' => $attempts,
        ]);
    }

    /**
     * Set cooldown period between attempts.
     */
    public function withCooldown(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'cooldown_minutes' => $minutes,
        ]);
    }

    /**
     * Enable re-take mode.
     */
    public function withRetake(): static
    {
        return $this->state(fn (array $attributes) => [
            'retake_enabled' => true,
        ]);
    }

    /**
     * Set review mode.
     */
    public function withReviewMode(ReviewMode $mode): static
    {
        return $this->state(fn (array $attributes) => [
            'review_mode' => $mode,
        ]);
    }

    /**
     * Set deferred review mode.
     */
    public function deferredReview(): static
    {
        return $this->withReviewMode(ReviewMode::Deferred);
    }

    /**
     * Set hidden review mode.
     */
    public function hiddenReview(): static
    {
        return $this->withReviewMode(ReviewMode::Hidden);
    }

    /**
     * Enable random question order.
     */
    public function withRandomOrder(): static
    {
        return $this->state(fn (array $attributes) => [
            'randomization_type' => RandomizationType::RandomOrder,
        ]);
    }

    /**
     * Enable question bank selection.
     */
    public function withQuestionBank(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'randomization_type' => RandomizationType::Bank,
            'question_bank_count' => $count,
        ]);
    }
}
