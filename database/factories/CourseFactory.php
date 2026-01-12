<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Common\Models\Category;
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
        return [
            'code' => strtoupper(Str::random(6)),
            'slug' => fake()->unique()->slug(),
            'title' => fake()->sentence(3),
            'short_desc' => fake()->paragraph(),
            'type' => fake()->randomElement(['okupasi', 'kluster']),
            'level_tag' => fake()->randomElement(['dasar', 'menengah', 'mahir']),
            'category_id' => Category::factory(),
            'tags_json' => [],
            'progression_mode' => fake()->randomElement(['sequential', 'free']),
            'enrollment_type' => 'auto_accept',
            'enrollment_key_hash' => null,
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
            'enrollment_key_hash' => null,
        ]);
    }

    /**
     * Indicate that the course uses key-based enrollment.
     */
    public function keyBased(): static
    {
        $keyHasher = app(\App\Contracts\EnrollmentKeyHasherInterface::class);

        return $this->state(fn (array $attributes) => [
            'enrollment_type' => 'key_based',
            'enrollment_key_hash' => $keyHasher->hash(Str::random(10)),
        ]);
    }
}
