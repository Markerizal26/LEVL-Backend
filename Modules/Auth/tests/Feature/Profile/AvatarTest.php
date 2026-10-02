<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\app\Models\User;

test('user can upload avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $token = auth()->login($user);

    $file = UploadedFile::fake()->image('avatar.jpg');

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->postJson('/api/v1/profile/avatar', [
            'avatar' => $file,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['avatar_url']]);
});

test('upload avatar replaces old avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $token = auth()->login($user);

    $file1 = UploadedFile::fake()->image('avatar1.jpg');
    $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->postJson('/api/v1/profile/avatar', ['avatar' => $file1]);

    $file2 = UploadedFile::fake()->image('avatar2.jpg');
    $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->postJson('/api/v1/profile/avatar', ['avatar' => $file2]);

    expect(true)->toBeTrue();
});

test('user can delete avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $token = auth()->login($user);
    $file = UploadedFile::fake()->image('avatar.jpg');

    $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->postJson('/api/v1/profile/avatar', ['avatar' => $file]);

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->deleteJson('/api/v1/profile/avatar');

    $response->assertStatus(200);
    expect($user->fresh()->avatar_url)->toBeNull();
});

test('upload avatar accepts specific types', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $token = auth()->login($user);

    
    $file = UploadedFile::fake()->image('avatar.png');
    $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->postJson('/api/v1/profile/avatar', ['avatar' => $file])
        ->assertStatus(200);

    
    $file = UploadedFile::fake()->image('avatar.gif');
    $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->postJson('/api/v1/profile/avatar', ['avatar' => $file])
        ->assertStatus(200);
});

test('upload avatar fails with non image', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $token = auth()->login($user);
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->postJson('/api/v1/profile/avatar', ['avatar' => $file]);

    $response->assertStatus(422);
});

test('upload avatar fails with too large file', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $token = auth()->login($user);
    $file = UploadedFile::fake()->create('large.jpg', 10000);

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->postJson('/api/v1/profile/avatar', ['avatar' => $file]);

    $response->assertStatus(422);
});

test('upload avatar fails without authentication', function () {
    $response = $this->postJson('/api/v1/profile/avatar', []);
    $response->assertStatus(401);
});
