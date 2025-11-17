<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Assert that a database record exists.
 */
function assertDatabaseHas(string $table, array $data): void
{
    expect(\Illuminate\Support\Facades\DB::table($table)->where($data)->exists())->toBeTrue();
}

/**
 * Assert that a database record does not exist.
 */
function assertDatabaseMissing(string $table, array $data): void
{
    expect(\Illuminate\Support\Facades\DB::table($table)->where($data)->exists())->toBeFalse();
}

/**
 * Create roles for testing.
 */
function createTestRoles(): void
{
    $guard = 'api';
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => $guard]);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin', 'guard_name' => $guard]);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Instructor', 'guard_name' => $guard]);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Student', 'guard_name' => $guard]);
}

/**
 * Get API URL with v1 prefix.
 */
function api(string $uri): string
{
    return "/api/v1" . $uri;
}
