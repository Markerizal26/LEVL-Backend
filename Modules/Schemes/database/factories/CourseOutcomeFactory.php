<?php

namespace Modules\Schemes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\CourseOutcome;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Schemes\Models\CourseOutcome>
 */
class CourseOutcomeFactory extends Factory
{
    protected $model = CourseOutcome::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'outcome_text' => fake()->sentence(),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
