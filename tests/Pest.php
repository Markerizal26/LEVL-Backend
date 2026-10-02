
<?php

uses(
    Tests\TestCase::class,
)
    ->in('Feature', 'Unit');

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



expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});




function assertDatabaseHas(string $table, array $data): void
{
    expect(\Illuminate\Support\Facades\DB::table($table)->where($data)->exists())->toBeTrue();
}


function assertDatabaseMissing(string $table, array $data): void
{
    expect(\Illuminate\Support\Facades\DB::table($table)->where($data)->exists())->toBeFalse();
}


function createTestRoles(): void
{
    $guard = 'api';
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => $guard]);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin', 'guard_name' => $guard]);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Instructor', 'guard_name' => $guard]);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Student', 'guard_name' => $guard]);
}


function api(string $uri): string
{
    return '/api/v1'.$uri;
}


function assertDatabaseCount(string $table, int $count): void
{
    expect(\Illuminate\Support\Facades\DB::table($table)->count())->toBe($count);
}


function createUserWithRole(string $role, array $attributes = []): \Modules\Auth\Models\User
{
    createTestRoles();
    $user = \Modules\Auth\Models\User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}


function authenticatedRequest(string $method, string $uri, array $data = [], ?\Modules\Auth\Models\User $user = null)
{
    if (! $user) {
        $user = \Modules\Auth\Models\User::factory()->create();
    }

    return test()->actingAs($user, 'api')->json($method, $uri, $data);
}


function seedTestRoles(): void
{
    (new \Database\Seeders\TestRolesSeeder)->run();
}


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


function assertValidationError(string $field, $response): void
{
    $response->assertStatus(422)
        ->assertJsonValidationErrors($field);
}


function assertValidationErrors(array $fields, $response): void
{
    $response->assertStatus(422)
        ->assertJsonValidationErrors($fields);
}


function freezeTime(string $time = 'now'): \Illuminate\Support\Carbon
{
    $carbon = \Illuminate\Support\Carbon::parse($time);
    \Illuminate\Support\Carbon::setTestNow($carbon);

    return $carbon;
}


function unfreezeTime(): void
{
    \Illuminate\Support\Carbon::setTestNow();
}


function assertEventDispatched(string $event): void
{
    \Illuminate\Support\Facades\Event::assertDispatched($event);
}


function assertJobPushed(string $job): void
{
    \Illuminate\Support\Facades\Queue::assertPushed($job);
}


function assertNotificationSent($notifiable, string $notification): void
{
    \Illuminate\Support\Facades\Notification::assertSentTo($notifiable, $notification);
}


function assertMailSent(string $mailable): void
{
    \Illuminate\Support\Facades\Mail::assertSent($mailable);
}
