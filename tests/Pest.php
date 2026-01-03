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

pest()->extend(Tests\TestCase::class)->in('Feature');

pest()->extend(Tests\TestCase::class)->in('Unit');

// Configure Pest for module tests (located at project root level Modules/*/tests)
pest()->extend(Tests\TestCase::class)->in('../Modules/Auth/tests');
pest()->extend(Tests\TestCase::class)->in('../Modules/Common/tests');
pest()->extend(Tests\TestCase::class)->in('../Modules/Content/tests');
pest()->extend(Tests\TestCase::class)->in('../Modules/Enrollments/tests');
pest()->extend(Tests\TestCase::class)->in('../Modules/Forums/tests');
pest()->extend(Tests\TestCase::class)->in('../Modules/Gamification/tests');
pest()->extend(Tests\TestCase::class)->in('../Modules/Grading/tests');
pest()->extend(Tests\TestCase::class)->in('../Modules/Learning/tests');
pest()->extend(Tests\TestCase::class)->in('../Modules/Notifications/tests');
pest()->extend(Tests\TestCase::class)->in('../Modules/Operations/tests');
pest()->extend(Tests\TestCase::class)->in('../Modules/Questions/tests');
pest()->extend(Tests\TestCase::class)->in('../Modules/Schemes/tests');
pest()->extend(Tests\TestCase::class)->in('../Modules/Search/tests');

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
    return '/api/v1'.$uri;
}

/**
 * Assert that a database record count matches expected value.
 */
function assertDatabaseCount(string $table, int $count): void
{
    expect(\Illuminate\Support\Facades\DB::table($table)->count())->toBe($count);
}

/**
 * Create a user with a specific role.
 */
function createUserWithRole(string $role, array $attributes = []): \Modules\Auth\app\Models\User
{
    createTestRoles();
    $user = \Modules\Auth\app\Models\User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

/**
 * Create an authenticated API request.
 */
function authenticatedRequest(string $method, string $uri, array $data = [], ?\Modules\Auth\app\Models\User $user = null)
{
    if (! $user) {
        $user = \Modules\Auth\app\Models\User::factory()->create();
    }

    return test()->actingAs($user, 'api')->json($method, $uri, $data);
}

/**
 * Seed test roles and permissions.
 */
function seedTestRoles(): void
{
    (new \Database\Seeders\TestRolesSeeder)->run();
}

/**
 * Assert JSON response structure matches expected structure.
 */
function assertJsonStructureMatches(array $expected, array $actual): void
{
    foreach ($expected as $key => $value) {
        if (is_array($value)) {
            expect($actual)->toHaveKey($key);
            assertJsonStructureMatches($value, $actual[$key]);
        } else {
            expect($actual)->toHaveKey($value);
        }
    }
}

/**
 * Create a temporary file for testing file uploads.
 */
function createTestFile(string $name = 'test.txt', string $content = 'test content'): \Illuminate\Http\UploadedFile
{
    $path = sys_get_temp_dir().'/'.$name;
    file_put_contents($path, $content);

    return new \Illuminate\Http\UploadedFile(
        $path,
        $name,
        mime_content_type($path),
        null,
        true
    );
}

/**
 * Create a test image file.
 */
function createTestImage(string $name = 'test.jpg', int $width = 100, int $height = 100): \Illuminate\Http\UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    $path = sys_get_temp_dir().'/'.$name;

    imagejpeg($image, $path);
    imagedestroy($image);

    return new \Illuminate\Http\UploadedFile(
        $path,
        $name,
        'image/jpeg',
        null,
        true
    );
}

/**
 * Assert that a validation error exists for a specific field.
 */
function assertValidationError(string $field, $response): void
{
    $response->assertStatus(422)
        ->assertJsonValidationErrors($field);
}

/**
 * Assert that multiple validation errors exist.
 */
function assertValidationErrors(array $fields, $response): void
{
    $response->assertStatus(422)
        ->assertJsonValidationErrors($fields);
}

/**
 * Freeze time for testing.
 */
function freezeTime(string $time = 'now'): \Illuminate\Support\Carbon
{
    $carbon = \Illuminate\Support\Carbon::parse($time);
    \Illuminate\Support\Carbon::setTestNow($carbon);

    return $carbon;
}

/**
 * Unfreeze time after testing.
 */
function unfreezeTime(): void
{
    \Illuminate\Support\Carbon::setTestNow();
}

/**
 * Assert that an event was dispatched.
 */
function assertEventDispatched(string $event): void
{
    \Illuminate\Support\Facades\Event::assertDispatched($event);
}

/**
 * Assert that a job was pushed to the queue.
 */
function assertJobPushed(string $job): void
{
    \Illuminate\Support\Facades\Queue::assertPushed($job);
}

/**
 * Assert that a notification was sent.
 */
function assertNotificationSent($notifiable, string $notification): void
{
    \Illuminate\Support\Facades\Notification::assertSentTo($notifiable, $notification);
}

/**
 * Assert that a mail was sent.
 */
function assertMailSent(string $mailable): void
{
    \Illuminate\Support\Facades\Mail::assertSent($mailable);
}
