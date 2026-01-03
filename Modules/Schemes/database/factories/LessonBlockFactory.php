<?php

namespace Modules\Schemes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\LessonBlock;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Schemes\Models\LessonBlock>
 */
class LessonBlockFactory extends Factory
{
    protected $model = LessonBlock::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'slug' => fake()->unique()->slug(),
            'block_type' => fake()->randomElement(['text', 'image', 'video', 'audio', 'code', 'quiz']),
            'content' => fake()->paragraphs(2, true),
            'order' => fake()->numberBetween(1, 10),
        ];
    }

    /**
     * Indicate that the block is a text block.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'block_type' => 'text',
            'content' => fake()->paragraphs(3, true),
        ]);
    }

    /**
     * Indicate that the block is an image block.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'block_type' => 'image',
            'content' => json_encode([
                'caption' => fake()->sentence(),
                'alt_text' => fake()->sentence(),
            ]),
        ]);
    }

    /**
     * Indicate that the block is a video block.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'block_type' => 'video',
            'content' => json_encode([
                'title' => fake()->sentence(),
                'description' => fake()->paragraph(),
            ]),
        ]);
    }

    /**
     * Indicate that the block is a code block.
     */
    public function code(): static
    {
        return $this->state(fn (array $attributes) => [
            'block_type' => 'code',
            'content' => json_encode([
                'language' => fake()->randomElement(['php', 'javascript', 'python', 'java']),
                'code' => fake()->text(200),
            ]),
        ]);
    }

    /**
     * Indicate that the block is a quiz block.
     */
    public function quiz(): static
    {
        return $this->state(fn (array $attributes) => [
            'block_type' => 'quiz',
            'content' => json_encode([
                'question' => fake()->sentence().'?',
                'options' => [
                    fake()->word(),
                    fake()->word(),
                    fake()->word(),
                    fake()->word(),
                ],
                'correct_answer' => 0,
            ]),
        ]);
    }
}
