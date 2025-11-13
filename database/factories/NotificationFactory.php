<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notifications\Models\Notification;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Notifications\Models\Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['system', 'assignment', 'assessment', 'grading', 'gamification', 'news', 'custom']),
            'title' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'channel' => fake()->randomElement(['in_app', 'email', 'push']),
            'priority' => fake()->randomElement(['low', 'normal', 'high']),
            'is_broadcast' => false,
            'scheduled_at' => null,
            'sent_at' => null,
        ];
    }

    /**
     * Indicate that the notification is a broadcast.
     */
    public function broadcast(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_broadcast' => true,
        ]);
    }

    /**
     * Indicate that the notification is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 week'),
        ]);
    }
}

