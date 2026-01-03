<?php

namespace Modules\Enrollments\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Models\UnitProgress;
use Modules\Schemes\Models\Unit;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Enrollments\Models\UnitProgress>
 */
class UnitProgressFactory extends Factory
{
    protected $model = UnitProgress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'unit_id' => Unit::factory(),
            'status' => fake()->randomElement(['not_started', 'in_progress', 'completed']),
            'progress_percent' => fake()->randomFloat(2, 0, 100),
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the unit progress is not started.
     */
    public function notStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'not_started',
            'progress_percent' => 0,
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the unit progress is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'progress_percent' => fake()->randomFloat(2, 1, 99),
            'started_at' => now()->subDays(rand(1, 7)),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the unit progress is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress_percent' => 100,
            'started_at' => now()->subDays(rand(1, 7)),
            'completed_at' => now(),
        ]);
    }
}
