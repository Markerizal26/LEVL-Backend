<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Enrollments\Models\Enrollment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Enrollments\Models\Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'course_id' => null,
            'status' => fake()->randomElement(['active', 'pending', 'completed', 'cancelled']),
            'enrolled_at' => now(),
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the enrollment is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
    }

    /**
     * Indicate that the enrollment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'enrolled_at' => now(),
        ]);
    }

    /**
     * Indicate that the enrollment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}

