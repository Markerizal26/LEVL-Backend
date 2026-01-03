<?php

namespace Modules\Auth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\LoginActivity;
use Modules\Auth\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Auth\Models\LoginActivity>
 */
class LoginActivityFactory extends Factory
{
    protected $model = LoginActivity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'status' => 'success',
            'logged_in_at' => now(),
            'logged_out_at' => null,
        ];
    }

    /**
     * Indicate that the login was successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
        ]);
    }

    /**
     * Indicate that the login failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'logged_out_at' => null,
        ]);
    }

    /**
     * Indicate that the session is active (logged in but not logged out).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'logged_in_at' => now()->subHours(rand(1, 24)),
            'logged_out_at' => null,
        ]);
    }

    /**
     * Indicate that the session is completed (logged out).
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'logged_in_at' => now()->subHours(rand(2, 48)),
            'logged_out_at' => now()->subHours(rand(1, 24)),
        ]);
    }
}
