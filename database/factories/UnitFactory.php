<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Schemes\Models\Unit;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Schemes\Models\Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(2);
        $slug = Str::slug($title);

        return [
            'course_id' => null,
            'code' => strtoupper(Str::random(4)),
            'slug' => $slug,
            'title' => $title,
            'description' => fake()->paragraph(),
            'order' => fake()->numberBetween(1, 10),
            'status' => 'published',
        ];
    }

    /**
     * Indicate that the unit is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }
}

