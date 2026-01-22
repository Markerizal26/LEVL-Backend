<?php

namespace Modules\Grading\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Grading\Models\Grade;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Grading\Models\Grade>
 */
class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        $maxScore = fake()->numberBetween(50, 100);

        return [
            'source_type' => 'assignment',
            'source_id' => Assignment::factory(),
            'submission_id' => null,
            'user_id' => User::factory(),
            'graded_by' => User::factory(),
            'score' => fake()->numberBetween(0, $maxScore),
            'original_score' => null,
            'max_score' => $maxScore,
            'is_override' => false,
            'override_reason' => null,
            'is_draft' => false,
            'feedback' => fake()->paragraph(),
            'status' => 'graded',
            'graded_at' => now(),
            'released_at' => null,
        ];
    }

    /**
     * Associate with a submission.
     */
    public function forSubmission(?Submission $submission = null): static
    {
        return $this->state(fn (array $attributes) => [
            'submission_id' => $submission?->id ?? Submission::factory(),
        ]);
    }

    /**
     * Indicate that the grade is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'graded_at' => null,
        ]);
    }

    /**
     * Indicate that the grade is graded.
     */
    public function graded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'graded',
            'graded_at' => now(),
        ]);
    }

    /**
     * Indicate that the grade is a perfect score.
     */
    public function perfect(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $attributes['max_score'],
        ]);
    }

    /**
     * Indicate that the grade is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_draft' => true,
        ]);
    }

    /**
     * Indicate that the grade has been released.
     */
    public function released(): static
    {
        return $this->state(fn (array $attributes) => [
            'released_at' => now(),
        ]);
    }

    /**
     * Indicate that the grade is an override.
     */
    public function override(float $originalScore, string $reason): static
    {
        return $this->state(fn (array $attributes) => [
            'original_score' => $originalScore,
            'is_override' => true,
            'override_reason' => $reason,
        ]);
    }
}
