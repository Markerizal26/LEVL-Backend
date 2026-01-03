<?php

namespace Modules\Auth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\PinnedBadge;
use Modules\Auth\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Auth\Models\PinnedBadge>
 */
class PinnedBadgeFactory extends Factory
{
    protected $model = PinnedBadge::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'badge_id' => fake()->numberBetween(1, 100),
            'order' => fake()->numberBetween(1, 10),
        ];
    }

    /**
     * Indicate the order of the pinned badge.
     */
    public function order(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
        ]);
    }
}
