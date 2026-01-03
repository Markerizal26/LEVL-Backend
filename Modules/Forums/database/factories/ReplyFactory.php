<?php

namespace Modules\Forums\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Forums\Models\Reply>
 */
class ReplyFactory extends Factory
{
    protected $model = Reply::class;

    public function definition(): array
    {
        return [
            'thread_id' => Thread::factory(),
            'author_id' => User::factory(),
            'content' => fake()->paragraphs(2, true),
            'is_solution' => false,
            'edited_at' => null,
            'deleted_by' => null,
        ];
    }

    public function solution(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_solution' => true,
        ]);
    }
}
