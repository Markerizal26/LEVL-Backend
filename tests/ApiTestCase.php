<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\app\Models\User;

/**
 * Base test case for API endpoint testing.
 *
 * Provides helper methods for authentication, API requests, and common assertions.
 */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Act as an authenticated user with the given attributes.
     *
     * @param  array  $attributes  User attributes
     */
    protected function actingAsUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $this->actingAs($user, 'api');

        return $user;
    }

    /**
     * Act as an authenticated admin user.
     */
    protected function actingAsAdmin(): User
    {
        $this->ensureRolesExist();
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $this->actingAs($user, 'api');

        return $user;
    }

    /**
     * Act as an authenticated instructor user.
     */
    protected function actingAsInstructor(): User
    {
        $this->ensureRolesExist();
        $user = User::factory()->create();
        $user->assignRole('Instructor');
        $this->actingAs($user, 'api');

        return $user;
    }

    /**
     * Act as an authenticated student user.
     */
    protected function actingAsStudent(): User
    {
        $this->ensureRolesExist();
        $user = User::factory()->create();
        $user->assignRole('Student');
        $this->actingAs($user, 'api');

        return $user;
    }

    /**
     * Act as an authenticated superadmin user.
     */
    protected function actingAsSuperadmin(): User
    {
        $this->ensureRolesExist();
        $user = User::factory()->create();
        $user->assignRole('Superadmin');
        $this->actingAs($user, 'api');

        return $user;
    }

    /**
     * Remove authentication for the current request.
     */
    protected function withoutAuthentication(): void
    {
        $this->app['auth']->forgetGuards();
    }

    /**
     * Assert that the response is a valid JSON API response with the given status.
     *
     * @param  \Illuminate\Testing\TestResponse  $response
     * @param  int  $status  Expected HTTP status code
     */
    protected function assertJsonApiResponse($response, int $status = 200): void
    {
        $response->assertStatus($status)
            ->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Assert that the response has a successful status code (2xx).
     *
     * @param  \Illuminate\Testing\TestResponse  $response
     */
    protected function assertSuccessfulResponse($response): void
    {
        $response->assertSuccessful()
            ->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Assert that the response has pagination metadata.
     *
     * @param  \Illuminate\Testing\TestResponse  $response
     */
    protected function assertHasPagination($response): void
    {
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'current_page',
                'total',
                'per_page',
                'last_page',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);
    }

    /**
     * Ensure test roles exist in the database.
     */
    protected function ensureRolesExist(): void
    {
        $guard = 'api';
        $roles = ['Superadmin', 'Admin', 'Instructor', 'Student'];

        foreach ($roles as $role) {
            \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => $role,
                'guard_name' => $guard,
            ]);
        }
    }

    /**
     * Get the API URL with v1 prefix.
     */
    protected function apiUrl(string $uri): string
    {
        return '/api/v1'.$uri;
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Additional API test setup can go here
        // For example: setting up default headers, etc.
    }
}
