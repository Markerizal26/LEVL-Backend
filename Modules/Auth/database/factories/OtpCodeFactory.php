<?php

namespace Modules\Auth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Auth\Models\OtpCode;
use Modules\Auth\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Auth\Models\OtpCode>
 */
class OtpCodeFactory extends Factory
{
    protected $model = OtpCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'channel' => fake()->randomElement(['email', 'sms']),
            'provider' => fake()->randomElement(['twilio', 'sendgrid', 'mailgun']),
            'purpose' => fake()->randomElement(['login', 'password_reset', 'email_verification']),
            'code' => str_pad((string) fake()->numberBetween(100000, 999999), 6, '0', STR_PAD_LEFT),
            'meta' => [],
            'expires_at' => now()->addMinutes(10),
            'consumed_at' => null,
        ];
    }

    /**
     * Indicate that the OTP code is for email verification.
     */
    public function forEmailVerification(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => 'email_verification',
            'channel' => 'email',
        ]);
    }

    /**
     * Indicate that the OTP code is for password reset.
     */
    public function forPasswordReset(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => 'password_reset',
            'channel' => 'email',
        ]);
    }

    /**
     * Indicate that the OTP code is for login.
     */
    public function forLogin(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => 'login',
        ]);
    }

    /**
     * Indicate that the OTP code is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(10),
        ]);
    }

    /**
     * Indicate that the OTP code is consumed.
     */
    public function consumed(): static
    {
        return $this->state(fn (array $attributes) => [
            'consumed_at' => now()->subMinutes(5),
        ]);
    }

    /**
     * Indicate that the OTP code is valid (not consumed and not expired).
     */
    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addMinutes(10),
            'consumed_at' => null,
        ]);
    }
}
