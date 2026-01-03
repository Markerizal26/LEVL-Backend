<?php

namespace Modules\Auth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Auth\Models\SocialAccount;
use Modules\Auth\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Auth\Models\SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_name' => fake()->randomElement(['google', 'facebook', 'github', 'twitter']),
            'provider_id' => fake()->unique()->numerify('##########'),
            'token' => Str::random(64),
            'refresh_token' => Str::random(64),
        ];
    }

    /**
     * Indicate that the social account is for Google.
     */
    public function google(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_name' => 'google',
        ]);
    }

    /**
     * Indicate that the social account is for Facebook.
     */
    public function facebook(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_name' => 'facebook',
        ]);
    }

    /**
     * Indicate that the social account is for GitHub.
     */
    public function github(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_name' => 'github',
        ]);
    }

    /**
     * Indicate that the social account is for Twitter.
     */
    public function twitter(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_name' => 'twitter',
        ]);
    }
}
