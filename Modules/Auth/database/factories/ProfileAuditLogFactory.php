<?php

namespace Modules\Auth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\ProfileAuditLog;
use Modules\Auth\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Auth\Models\ProfileAuditLog>
 */
class ProfileAuditLogFactory extends Factory
{
    protected $model = ProfileAuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'admin_id' => User::factory(),
            'action' => fake()->randomElement(['profile_updated', 'status_changed', 'role_assigned', 'password_reset']),
            'changes' => [
                'field' => fake()->word(),
                'old_value' => fake()->word(),
                'new_value' => fake()->word(),
            ],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Indicate that the action is a profile update.
     */
    public function profileUpdated(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'profile_updated',
            'changes' => [
                'field' => 'name',
                'old_value' => fake()->name(),
                'new_value' => fake()->name(),
            ],
        ]);
    }

    /**
     * Indicate that the action is a status change.
     */
    public function statusChanged(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'status_changed',
            'changes' => [
                'field' => 'status',
                'old_value' => 'active',
                'new_value' => 'suspended',
            ],
        ]);
    }

    /**
     * Indicate that the action is a role assignment.
     */
    public function roleAssigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'role_assigned',
            'changes' => [
                'role' => fake()->randomElement(['Admin', 'Instructor', 'Student']),
            ],
        ]);
    }
}
