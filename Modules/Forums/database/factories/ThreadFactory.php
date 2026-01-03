<?php

namespace Modules\Forums\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Thread;
use Modules\Schemes\Models\Course;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Forums\Models\Thread>
 */
class ThreadFactory extends Factory
{
    protected $model = Thread::class;

    public function definition(): array
    {
        return [
            'scheme_id' => Course::factory(),
            'author_id' => User::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'is_pinned' => false,
            'is_closed' => false,
            'is_resolved' => false,
            'views_count' => fake()->numberBetween(0, 1000),
            'replies_count' => 0,
            'last_activity_at' => now(),
            'edited_at' => null,
            'deleted_by' => null,
        ];
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_closed' => true,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_resolved' => true,
        ]);
    }

    public function withReplies(int $count = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'replies_count' => $count,
        ]);
    }
}
