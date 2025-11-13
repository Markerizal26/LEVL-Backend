<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Schemes\Models\Lesson;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Schemes\Models\Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

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
            'unit_id' => null,
            'slug' => $slug,
            'title' => $title,
            'description' => fake()->paragraph(),
            'markdown_content' => fake()->paragraphs(3, true),
            'content_type' => fake()->randomElement(['markdown', 'video', 'link']),
            'content_url' => null,
            'order' => fake()->numberBetween(1, 10),
            'duration_minutes' => fake()->numberBetween(10, 60),
            'status' => 'published',
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the lesson is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}

