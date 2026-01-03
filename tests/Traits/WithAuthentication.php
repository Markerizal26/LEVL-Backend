<?php

namespace Tests\Traits;

use Modules\Auth\app\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Trait for handling authentication in tests.
 */
trait WithAuthentication
{
    /**
     * Create and authenticate a user with the given role.
     */
    protected function authenticateAs(string $role, array $attributes = []): User
    {
        $this->ensureRolesExist();

        $user = User::factory()->create($attributes);
        $user->assignRole($role);
        $this->actingAs($user, 'api');

        return $user;
    }

    /**
     * Create and authenticate a superadmin user.
     */
    protected function authenticateAsSuperadmin(array $attributes = []): User
    {
        return $this->authenticateAs('Superadmin', $attributes);
    }

    /**
     * Create and authenticate an admin user.
     */
    protected function authenticateAsAdmin(array $attributes = []): User
    {
        return $this->authenticateAs('Admin', $attributes);
    }

    /**
     * Create and authenticate an instructor user.
     */
    protected function authenticateAsInstructor(array $attributes = []): User
    {
        return $this->authenticateAs('Instructor', $attributes);
    }

    /**
     * Create and authenticate a student user.
     */
    protected function authenticateAsStudent(array $attributes = []): User
    {
        return $this->authenticateAs('Student', $attributes);
    }

    /**
     * Ensure test roles exist in the database.
     */
    protected function ensureRolesExist(): void
    {
        $guard = 'api';
        $roles = ['Superadmin', 'Admin', 'Instructor', 'Student'];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => $guard,
            ]);
        }
    }

    /**
     * Remove authentication for the current request.
     */
    protected function unauthenticate(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
