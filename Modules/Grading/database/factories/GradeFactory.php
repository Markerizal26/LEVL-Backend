<?php

namespace Modules\Grading\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Grading\Models\Grade;
use Modules\Learning\Models\Assignment;

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
            'user_id' => User::factory(),
            'graded_by' => User::factory(),
            'score' => fake()->numberBetween(0, $maxScore),
            'max_score' => $maxScore,
            'feedback' => fake()->paragraph(),
            'status' => 'graded',
            'graded_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'graded_at' => null,
        ]);
    }

    public function graded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'graded',
            'graded_at' => now(),
        ]);
    }

    public function perfect(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $attributes['max_score'],
        ]);
    }
}
