<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Schemes\Models\Course;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Schemes\Models\Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);
        $slug = Str::slug($title);

        return [
            'code' => strtoupper(Str::random(6)),
            'slug' => $slug,
            'title' => $title,
            'short_desc' => fake()->paragraph(),
            'type' => fake()->randomElement(['okupasi', 'kluster']),
            'level_tag' => fake()->randomElement(['dasar', 'menengah', 'mahir']),
            'tags_json' => [],
            'progression_mode' => fake()->randomElement(['sequential', 'free']),
            'enrollment_type' => 'auto_accept',
            'enrollment_key' => null,
            'status' => 'published',
            'published_at' => now(),
            'instructor_id' => null,
        ];
    }

    /**
     * Indicate that the course is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the course requires approval.
     */
    public function approval(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_type' => 'approval',
            'enrollment_key' => null,
        ]);
    }

    /**
     * Indicate that the course uses key-based enrollment.
     */
    public function keyBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_type' => 'key_based',
            'enrollment_key' => Str::random(10),
        ]);
    }
}

