<?php

namespace Modules\Auth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Auth\Models\UserActivity>
 */
class UserActivityFactory extends Factory
{
    protected $model = UserActivity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'activity_type' => fake()->randomElement([
                UserActivity::TYPE_ENROLLMENT,
                UserActivity::TYPE_COMPLETION,
                UserActivity::TYPE_SUBMISSION,
                UserActivity::TYPE_ACHIEVEMENT,
                UserActivity::TYPE_BADGE_EARNED,
                UserActivity::TYPE_CERTIFICATE_EARNED,
            ]),
            'activity_data' => [
                'title' => fake()->sentence(),
                'description' => fake()->paragraph(),
            ],
            'related_type' => null,
            'related_id' => null,
        ];
    }

    /**
     * Indicate that the activity is an enrollment.
     */
    public function enrollment(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => UserActivity::TYPE_ENROLLMENT,
        ]);
    }

    /**
     * Indicate that the activity is a completion.
     */
    public function completion(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => UserActivity::TYPE_COMPLETION,
        ]);
    }

    /**
     * Indicate that the activity is a submission.
     */
    public function submission(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => UserActivity::TYPE_SUBMISSION,
        ]);
    }

    /**
     * Indicate that the activity is an achievement.
     */
    public function achievement(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => UserActivity::TYPE_ACHIEVEMENT,
        ]);
    }

    /**
     * Indicate that the activity is a badge earned.
     */
    public function badgeEarned(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => UserActivity::TYPE_BADGE_EARNED,
        ]);
    }

    /**
     * Indicate that the activity is a certificate earned.
     */
    public function certificateEarned(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => UserActivity::TYPE_CERTIFICATE_EARNED,
        ]);
    }
}
