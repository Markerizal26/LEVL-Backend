<?php

namespace Modules\Auth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Auth\Models\ProfilePrivacySetting>
 */
class ProfilePrivacySettingFactory extends Factory
{
    protected $model = ProfilePrivacySetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PUBLIC,
            'show_email' => true,
            'show_phone' => false,
            'show_activity_history' => true,
            'show_achievements' => true,
            'show_statistics' => true,
        ];
    }

    /**
     * Indicate that the profile is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PUBLIC,
            'show_email' => true,
            'show_phone' => true,
            'show_activity_history' => true,
            'show_achievements' => true,
            'show_statistics' => true,
        ]);
    }

    /**
     * Indicate that the profile is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PRIVATE,
            'show_email' => false,
            'show_phone' => false,
            'show_activity_history' => false,
            'show_achievements' => false,
            'show_statistics' => false,
        ]);
    }

    /**
     * Indicate that the profile is friends only.
     */
    public function friendsOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_FRIENDS,
        ]);
    }
}
