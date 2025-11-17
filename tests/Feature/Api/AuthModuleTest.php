<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Auth\Mail\ChangeEmailVerificationMail;
use Modules\Auth\Mail\ResetPasswordMail;
use Modules\Auth\Mail\VerifyEmailLinkMail;
use Modules\Auth\Models\JwtRefreshToken;
use Modules\Auth\Models\OtpCode;
use Modules\Auth\Models\PasswordResetToken;
use Modules\Auth\Models\User;
use Modules\Auth\Services\EmailVerificationService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    createTestRoles();
    Mail::fake();

    $this->superadmin = User::factory()->create([
        'email' => 'superadmin@example.com',
        'username' => 'superadmin',
    ]);
    $this->superadmin->assignRole('Superadmin');

    $this->admin = User::factory()->create([
        'email' => 'admin@example.com',
        'username' => 'adminuser',
    ]);
    $this->admin->assignRole('Admin');

    $this->instructor = User::factory()->create([
        'email' => 'instructor@example.com',
        'username' => 'instructoruser',
    ]);
    $this->instructor->assignRole('Instructor');

    $this->student = User::factory()->create([
        'email' => 'student@example.com',
        'username' => 'studentuser',
    ]);
    $this->student->assignRole('Student');
});

it('registers a new user with verification link', function () {
    $response = $this->postJson(api('/auth/register'), [
        'name' => 'New Student',
        'username' => 'newstudent',
        'email' => 'newstudent@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.user.email', 'newstudent@example.com')
        ->assertJsonPath('data.user.roles.0', 'Student')
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'expires_in', 'user']]);

    expect(User::whereEmail('newstudent@example.com')->exists())->toBeTrue();
    Mail::assertSent(VerifyEmailLinkMail::class);
});

it('validates register request input', function () {
    $response = $this->postJson(api('/auth/register'), []);

    $response->assertStatus(422)
        ->assertJsonStructure(['message', 'errors']);
});

it('logs in active user and returns token pair', function () {
    $user = User::factory()->create([
        'email' => 'loginuser@example.com',
        'username' => 'loginuser',
        'password' => Hash::make('Secret123!'),
    ]);
    $user->assignRole('Student');

    $response = $this->postJson(api('/auth/login'), [
        'login' => 'loginuser@example.com',
        'password' => 'Secret123!',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'expires_in', 'user']])
        ->assertJsonPath('data.user.email', 'loginuser@example.com');
});

it('rejects login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'wrongpassword@example.com',
        'username' => 'wrongpassword',
        'password' => Hash::make('Secret123!'),
    ]);
    $user->assignRole('Student');

    $response = $this->postJson(api('/auth/login'), [
        'login' => 'wrongpassword@example.com',
        'password' => 'invalid',
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure(['message', 'errors']);
});

it('refreshes token with valid refresh token', function () {
    $user = User::factory()->create([
        'email' => 'refresh@example.com',
        'username' => 'refreshuser',
        'password' => Hash::make('Secret123!'),
    ]);
    $user->assignRole('Student');

    $login = $this->postJson(api('/auth/login'), [
        'login' => 'refresh@example.com',
        'password' => 'Secret123!',
    ])->assertStatus(200);

    $refreshToken = $login->json('data.refresh_token');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$login->json('data.access_token'),
    ])->postJson(api('/auth/refresh'), [
        'refresh_token' => $refreshToken,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'expires_in']]);
});

it('rejects refresh with invalid token', function () {
    $login = $this->postJson(api('/auth/login'), [
        'login' => 'student@example.com',
        'password' => 'password',
    ])->assertStatus(200);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$login->json('data.access_token'),
    ])->postJson(api('/auth/refresh'), [
        'refresh_token' => 'invalid-token',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('message', 'Refresh token tidak valid atau tidak cocok dengan akun saat ini.');
});

it('logs out user and revokes refresh token', function () {
    $user = User::factory()->create([
        'email' => 'logout@example.com',
        'username' => 'logoutuser',
        'password' => Hash::make('Secret123!'),
    ]);
    $user->assignRole('Student');

    $login = $this->postJson(api('/auth/login'), [
        'login' => 'logout@example.com',
        'password' => 'Secret123!',
    ])->assertStatus(200);

    $refreshToken = $login->json('data.refresh_token');
    $accessToken = $login->json('data.access_token');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$accessToken,
    ])->postJson(api('/auth/logout'), [
        'refresh_token' => $refreshToken,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Logout berhasil.');

    $hashed = hash('sha256', $refreshToken);
    expect(JwtRefreshToken::where('token', $hashed)->whereNotNull('revoked_at')->exists())->toBeTrue();
});

it('requires bearer token to logout', function () {
    $login = $this->postJson(api('/auth/login'), [
        'login' => 'student@example.com',
        'password' => 'password',
    ])->assertStatus(200);

    $response = $this->postJson(api('/auth/logout'), [
        'refresh_token' => 'token',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('message', 'Anda belum login atau sesi Anda telah berakhir. Silakan login kembali.');
});

it('returns authenticated profile data', function () {
    $response = $this->actingAs($this->student, 'api')->getJson(api('/profile'));

    $response->assertStatus(200)
        ->assertJsonPath('data.email', 'student@example.com');
});

it('blocks unauthenticated profile access', function () {
    $response = $this->getJson(api('/profile'));

    $response->assertStatus(401);
});

it('updates profile information', function () {
    $response = $this->actingAs($this->student, 'api')->putJson(api('/profile'), [
        'name' => 'Updated Student',
        'username' => 'studentuser1',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Student')
        ->assertJsonPath('data.username', 'studentuser1');

    $this->student->refresh();
    expect($this->student->name)->toBe('Updated Student');
});

it('requires authentication to update profile', function () {
    $response = $this->putJson(api('/profile'), [
        'name' => 'No Auth',
        'username' => 'noauth',
    ]);

    $response->assertStatus(401);
});

it('allows user without username to set one', function () {
    $user = User::factory()->create([
        'email' => 'nousername@example.com',
        'username' => null,
    ]);
    $user->assignRole('Student');

    $response = $this->actingAs($user, 'api')->postJson(api('/auth/set-username'), [
        'username' => 'brandnewusername',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.user.username', 'brandnewusername');
});

it('prevents setting username when already set', function () {
    $response = $this->actingAs($this->student, 'api')->postJson(api('/auth/set-username'), [
        'username' => 'anotherusername',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Username sudah diatur untuk akun Anda.');
});

it('sends email verification link for pending user', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'pending@example.com',
        'username' => 'pendinguser',
        'status' => 'pending',
    ]);
    $user->assignRole('Student');

    $response = $this->actingAs($user, 'api')->postJson(api('/auth/email/verify/send'));

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Tautan verifikasi telah dikirim ke email Anda. Berlaku 3 menit dan hanya bisa digunakan sekali.');
    Mail::assertSent(VerifyEmailLinkMail::class);
    expect(OtpCode::where('user_id', $user->id)->forPurpose(EmailVerificationService::PURPOSE)->count())->toBe(1);
});

it('acknowledges already verified email', function () {
    $response = $this->actingAs($this->student, 'api')->postJson(api('/auth/email/verify/send'));

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Email Anda sudah terverifikasi.');
});

it('verifies email using uuid and code', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'verify@example.com',
        'username' => 'verifyuser',
        'status' => 'pending',
    ]);
    $user->assignRole('Student');

    $otp = OtpCode::create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $user->id,
        'channel' => 'email',
        'provider' => 'mailhog',
        'purpose' => EmailVerificationService::PURPOSE,
        'code' => '123456',
        'meta' => ['token_hash' => hash('sha256', 'ABCDEFGHIJKLMNOP')],
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson(api('/auth/email/verify'), [
        'uuid' => $otp->uuid,
        'code' => '123456',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Email Anda berhasil diverifikasi.');

    $user->refresh();
    expect($user->email_verified_at)->not()->toBeNull();
});

it('rejects email verification with invalid code', function () {
    $otp = OtpCode::create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $this->student->id,
        'channel' => 'email',
        'provider' => 'mailhog',
        'purpose' => EmailVerificationService::PURPOSE,
        'code' => '654321',
        'meta' => ['token_hash' => hash('sha256', 'ABCDEFGHIJKLMNOP')],
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson(api('/auth/email/verify'), [
        'uuid' => $otp->uuid,
        'code' => '000000',
    ]);

    $response->assertStatus(422);
});

it('verifies email using token link', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'tokenverify@example.com',
        'username' => 'tokenverify',
        'status' => 'pending',
    ]);
    $user->assignRole('Student');

    $token = 'ABCDEFGHIJKL1234';
    OtpCode::create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $user->id,
        'channel' => 'email',
        'provider' => 'mailhog',
        'purpose' => EmailVerificationService::PURPOSE,
        'code' => '123456',
        'meta' => ['token_hash' => hash('sha256', $token)],
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson(api('/auth/email/verify/by-token'), [
        'token' => $token,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Email Anda berhasil diverifikasi.');
});

it('returns not found for unknown verification token', function () {
    $response = $this->postJson(api('/auth/email/verify/by-token'), [
        'token' => 'ABCDEFGHIJKLMNOP',
    ]);

    $response->assertStatus(404);
});

it('requests email change and sends verification', function () {
    $user = User::factory()->create([
        'email' => 'change@example.com',
        'username' => 'changeuser',
    ]);
    $user->assignRole('Student');

    $response = $this->actingAs($user, 'api')->postJson(api('/profile/email/request'), [
        'new_email' => 'newchange@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['uuid']]);

    Mail::assertSent(ChangeEmailVerificationMail::class);
});

it('requires authentication to request email change', function () {
    $response = $this->postJson(api('/profile/email/request'), [
        'new_email' => 'unauth@example.com',
    ]);

    $response->assertStatus(401);
});

it('verifies email change successfully', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'username' => 'olduser',
    ]);
    $user->assignRole('Student');

    $otp = OtpCode::create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $user->id,
        'channel' => 'email',
        'provider' => 'mailhog',
        'purpose' => EmailVerificationService::PURPOSE_CHANGE_EMAIL,
        'code' => '123456',
        'meta' => ['new_email' => 'updated@example.com'],
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->actingAs($user, 'api')->postJson(api('/profile/email/verify'), [
        'uuid' => $otp->uuid,
        'code' => '123456',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Email berhasil diubah dan terverifikasi.');

    $user->refresh();
    expect($user->email)->toBe('updated@example.com');
});

it('rejects expired email change verification', function () {
    $otp = OtpCode::create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $this->student->id,
        'channel' => 'email',
        'provider' => 'mailhog',
        'purpose' => EmailVerificationService::PURPOSE_CHANGE_EMAIL,
        'code' => '654321',
        'meta' => ['new_email' => 'ignored@example.com'],
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($this->student, 'api')->postJson(api('/profile/email/verify'), [
        'uuid' => $otp->uuid,
        'code' => '654321',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Kode verifikasi telah kedaluwarsa.');
});

it('denies instructor creation for admin role', function () {
    $response = $this->actingAs($this->admin, 'api')->postJson(api('/auth/instructor'), [
        'name' => 'Instructor Created',
        'username' => 'createdinstructor',
        'email' => 'createdinstructor@example.com',
    ]);

    $response->assertStatus(403);
});

it('allows superadmin to create instructor accounts', function () {
    $response = $this->actingAs($this->superadmin, 'api')->postJson(api('/auth/instructor'), [
        'name' => 'Instructor Created',
        'username' => 'createdinstructor',
        'email' => 'createdinstructor@example.com',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.user.roles.0', 'Instructor');
});

it('denies instructor creation for student role', function () {
    $response = $this->actingAs($this->student, 'api')->postJson(api('/auth/instructor'), [
        'name' => 'Not Allowed',
        'username' => 'notallowed',
        'email' => 'notallowed@example.com',
    ]);

    $response->assertStatus(403);
});

it('allows superadmin to create admin accounts', function () {
    $response = $this->actingAs($this->superadmin, 'api')->postJson(api('/auth/admin'), [
        'name' => 'Admin Created',
        'username' => 'createdadmin',
        'email' => 'createdadmin@example.com',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.user.roles.0', 'Admin');
});

it('blocks admin creation for admin role', function () {
    $response = $this->actingAs($this->admin, 'api')->postJson(api('/auth/admin'), [
        'name' => 'Blocked Admin',
        'username' => 'blockedadmin',
        'email' => 'blockedadmin@example.com',
    ]);

    $response->assertStatus(403);
});

it('allows superadmin to create superadmin accounts', function () {
    $response = $this->actingAs($this->superadmin, 'api')->postJson(api('/auth/super-admin'), [
        'name' => 'Second Super',
        'username' => 'secondsuper',
        'email' => 'secondsuper@example.com',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.user.roles.0', 'Superadmin');
});

it('blocks superadmin creation for admin role', function () {
    $response = $this->actingAs($this->admin, 'api')->postJson(api('/auth/super-admin'), [
        'name' => 'Forbidden Super',
        'username' => 'forbiddensuper',
        'email' => 'forbiddensuper@example.com',
    ]);

    $response->assertStatus(403);
});

it('resends credentials for pending instructor', function () {
    $pending = User::factory()->create([
        'email' => 'pendinginstructor@example.com',
        'username' => 'pendinginstructor',
        'status' => 'pending',
    ]);
    $pending->assignRole('Instructor');

    $response = $this->actingAs($this->superadmin, 'api')->postJson(api('/auth/credentials/resend'), [
        'user_id' => $pending->id,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Kredensial berhasil dikirim ulang.');
});

it('rejects resend credentials for non-pending user', function () {
    $response = $this->actingAs($this->superadmin, 'api')->postJson(api('/auth/credentials/resend'), [
        'user_id' => $this->student->id,
    ]);

    $response->assertStatus(422);
});

it('updates user status as superadmin', function () {
    $user = User::factory()->create([
        'email' => 'statususer@example.com',
        'username' => 'statususer',
        'status' => 'inactive',
    ]);
    $user->assignRole('Student');

    $response = $this->actingAs($this->superadmin, 'api')->putJson(api("/auth/users/{$user->id}/status"), [
        'status' => 'active',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.user.status', 'active');
});

it('prevents updating user status back to pending', function () {
    $user = User::factory()->create([
        'email' => 'pendingstatus@example.com',
        'username' => 'pendingstatus',
        'status' => 'inactive',
    ]);
    $user->assignRole('Student');

    $response = $this->actingAs($this->superadmin, 'api')->putJson(api("/auth/users/{$user->id}/status"), [
        'status' => 'pending',
    ]);

    $response->assertStatus(422);
});

it('lists users for superadmin', function () {
    $response = $this->actingAs($this->superadmin, 'api')->getJson(api('/auth/users'));

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['items', 'meta']]);
});

it('allows admin to list users', function () {
    $response = $this->actingAs($this->admin, 'api')->getJson(api('/auth/users'));

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['items', 'meta']]);
});

it('prevents student listing users', function () {
    $response = $this->actingAs($this->student, 'api')->getJson(api('/auth/users'));

    $response->assertStatus(403);
});

it('shows user details for superadmin', function () {
    $response = $this->actingAs($this->superadmin, 'api')->getJson(api("/auth/users/{$this->student->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.user.email', 'student@example.com');
});

it('blocks user details for non superadmin', function () {
    $response = $this->actingAs($this->admin, 'api')->getJson(api("/auth/users/{$this->student->id}"));

    $response->assertStatus(403);
});

it('sends forgot password email without leaking existence', function () {
    $response = $this->postJson(api('/auth/password/forgot'), [
        'login' => 'student@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Jika email atau username terdaftar, kami telah mengirimkan instruksi reset kata sandi.');
    Mail::assertSent(ResetPasswordMail::class);
});

it('confirms forgot password and resets password', function () {
    $user = User::factory()->create([
        'email' => 'reset@example.com',
        'username' => 'resetuser',
        'password' => Hash::make('OldPassword1!'),
    ]);
    $user->assignRole('Student');

    $token = '123456';
    PasswordResetToken::create([
        'email' => $user->email,
        'token' => Hash::make($token),
        'created_at' => now(),
    ]);

    $newPassword = 'S3cure!'.Str::random(12);

    $response = $this->postJson(api('/auth/password/forgot/confirm'), [
        'token' => $token,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Kata sandi berhasil direset.');

    $user->refresh();
    expect(Hash::check($newPassword, $user->password))->toBeTrue();
});

it('rejects password reset with invalid token', function () {
    PasswordResetToken::create([
        'email' => 'ghost@example.com',
        'token' => Hash::make('654321'),
        'created_at' => now(),
    ]);

    $response = $this->postJson(api('/auth/password/forgot/confirm'), [
        'token' => '000000',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertStatus(422);
});

it('resets password for authenticated user', function () {
    $user = User::factory()->create([
        'email' => 'changepass@example.com',
        'username' => 'changepass',
        'password' => Hash::make('OldPassword1!'),
    ]);
    $user->assignRole('Student');

    $newPassword = 'S3cure!'.Str::random(12);

    $response = $this->actingAs($user, 'api')->postJson(api('/auth/password/reset'), [
        'current_password' => 'OldPassword1!',
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Kata sandi berhasil diperbarui.');

    $user->refresh();
    expect(Hash::check($newPassword, $user->password))->toBeTrue();
});

it('rejects password reset with wrong current password', function () {
    $newPassword = 'S3cure!'.Str::random(12);

    $response = $this->actingAs($this->student, 'api')->postJson(api('/auth/password/reset'), [
        'current_password' => 'wrong',
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Password lama tidak cocok.');
});

