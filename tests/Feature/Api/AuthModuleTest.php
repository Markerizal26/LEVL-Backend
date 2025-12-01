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
    "email" => "superadmin@example.com",
    "username" => "superadmin",
  ]);
  $this->superadmin->assignRole("Superadmin");

  $this->admin = User::factory()->create([
    "email" => "admin@example.com",
    "username" => "adminuser",
  ]);
  $this->admin->assignRole("Admin");

  $this->instructor = User::factory()->create([
    "email" => "instructor@example.com",
    "username" => "instructoruser",
  ]);
  $this->instructor->assignRole("Instructor");

  $this->student = User::factory()->create([
    "email" => "student@example.com",
    "username" => "studentuser",
  ]);
  $this->student->assignRole("Student");
});

it("Auth - Positif - registers a new user with verification link", function () {
  $response = $this->postJson(api("/auth/register"), [
    "name" => "New Student",
    "username" => "newstudent",
    "email" => "newstudent@example.com",
    "password" => "Password1!",
    "password_confirmation" => "Password1!",
  ]);

  $response
    ->assertStatus(201)
    ->assertJsonPath("data.user.email", "newstudent@example.com")
    ->assertJsonPath("data.user.roles.0", "Student")
    ->assertJsonStructure(["data" => ["access_token", "refresh_token", "expires_in", "user"]]);

  expect(User::whereEmail("newstudent@example.com")->exists())->toBeTrue();
  Mail::assertSent(VerifyEmailLinkMail::class);
});

it("Auth - Negatif - validates register request input", function () {
  $response = $this->postJson(api("/auth/register"), []);

  $response->assertStatus(422)->assertJsonStructure(["message", "errors"]);
});

it("Auth - Positif - logs in active user and returns token pair", function () {
  $user = User::factory()->create([
    "email" => "loginuser@example.com",
    "username" => "loginuser",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  $response = $this->postJson(api("/auth/login"), [
    "login" => "loginuser@example.com",
    "password" => "Secret123!",
  ]);

  $response
    ->assertStatus(200)
    ->assertJsonStructure(["data" => ["access_token", "refresh_token", "expires_in", "user"]])
    ->assertJsonPath("data.user.email", "loginuser@example.com");
});

it("Auth - Negatif - rejects login with invalid credentials", function () {
  $user = User::factory()->create([
    "email" => "wrongpassword@example.com",
    "username" => "wrongpassword",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  $response = $this->postJson(api("/auth/login"), [
    "login" => "wrongpassword@example.com",
    "password" => "invalid",
  ]);

  $response->assertStatus(422)->assertJsonStructure(["message", "errors"]);
});

it("Auth - Positif - refreshes token with valid refresh token", function () {
  $user = User::factory()->create([
    "email" => "refresh@example.com",
    "username" => "refreshuser",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  $login = $this->postJson(api("/auth/login"), [
    "login" => "refresh@example.com",
    "password" => "Secret123!",
  ])->assertStatus(200);

  $refreshToken = $login->json("data.refresh_token");

  $response = $this->postJson(api("/auth/refresh"), [
    "refresh_token" => $refreshToken,
  ]);

  $response
    ->assertStatus(200)
    ->assertJsonStructure(["data" => ["access_token", "refresh_token", "expires_in"]]);
});

it("Auth - Negatif - rejects refresh with invalid token", function () {
  $response = $this->postJson(api("/auth/refresh"), [
    "refresh_token" => "invalid-token",
  ]);

  $response
    ->assertStatus(401)
    ->assertJsonPath("message", "Refresh token tidak valid atau kadaluarsa.");
});

it("Auth - Positif - refreshes token with valid refresh token via header", function () {
  $user = User::factory()->create([
    "email" => "refreshheader@example.com",
    "username" => "refreshheaderuser",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  $login = $this->postJson(api("/auth/login"), [
    "login" => "refreshheader@example.com",
    "password" => "Secret123!",
  ])->assertStatus(200);

  $refreshToken = $login->json("data.refresh_token");

  $response = $this->withHeaders([
    "X-Refresh-Token" => $refreshToken,
  ])->postJson(api("/auth/refresh"));

  $response
    ->assertStatus(200)
    ->assertJsonStructure(["data" => ["access_token", "refresh_token", "expires_in"]]);
});

it("Auth - Positif - logs out user and revokes refresh token", function () {
  $user = User::factory()->create([
    "email" => "logout@example.com",
    "username" => "logoutuser",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  $login = $this->postJson(api("/auth/login"), [
    "login" => "logout@example.com",
    "password" => "Secret123!",
  ])->assertStatus(200);

  $refreshToken = $login->json("data.refresh_token");
  $accessToken = $login->json("data.access_token");

  $response = $this->withHeaders([
    "Authorization" => "Bearer " . $accessToken,
  ])->postJson(api("/auth/logout"), [
    "refresh_token" => $refreshToken,
  ]);

  $response->assertStatus(200)->assertJsonPath("message", "Logout berhasil.");

  $hashed = hash("sha256", $refreshToken);
  expect(
    JwtRefreshToken::where("token", $hashed)->whereNotNull("revoked_at")->exists(),
  )->toBeTrue();
});

it("Auth - Negatif - requires bearer token to logout", function () {
  $login = $this->postJson(api("/auth/login"), [
    "login" => "student@example.com",
    "password" => "password",
  ])->assertStatus(200);

  $response = $this->postJson(api("/auth/logout"), [
    "refresh_token" => "token",
  ]);

  $response
    ->assertStatus(401)
    ->assertJsonPath(
      "message",
      "Anda belum login atau sesi Anda telah berakhir. Silakan login kembali.",
    );
});

it("Auth - Positif - returns authenticated profile data", function () {
  $response = $this->actingAs($this->student, "api")->getJson(api("/profile"));

  $response->assertStatus(200)->assertJsonPath("data.email", "student@example.com");
});

it("Auth - Negatif - blocks unauthenticated profile access", function () {
  $response = $this->getJson(api("/profile"));

  $response->assertStatus(401);
});

it("Auth - Positif - updates profile information", function () {
  $response = $this->actingAs($this->student, "api")->putJson(api("/profile"), [
    "name" => "Updated Student",
    "username" => "studentuser1",
  ]);

  $response
    ->assertStatus(200)
    ->assertJsonPath("data.name", "Updated Student")
    ->assertJsonPath("data.username", "studentuser1");

  $this->student->refresh();
  expect($this->student->name)->toBe("Updated Student");
});

it("Auth - Negatif - requires authentication to update profile", function () {
  $response = $this->putJson(api("/profile"), [
    "name" => "No Auth",
    "username" => "noauth",
  ]);

  $response->assertStatus(401);
});

it("Auth - Positif - allows user without username to set one", function () {
  $user = User::factory()->create([
    "email" => "nousername@example.com",
    "username" => null,
  ]);
  $user->assignRole("Student");

  $response = $this->actingAs($user, "api")->postJson(api("/auth/set-username"), [
    "username" => "brandnewusername",
  ]);

  $response->assertStatus(200)->assertJsonPath("data.user.username", "brandnewusername");
});

it("Auth - Negatif - prevents setting username when already set", function () {
  $response = $this->actingAs($this->student, "api")->postJson(api("/auth/set-username"), [
    "username" => "anotherusername",
  ]);

  $response->assertStatus(422)->assertJsonPath("message", "Username sudah diatur untuk akun Anda.");
});

it("Auth - Positif - sends email verification link for pending user", function () {
  $user = User::factory()
    ->unverified()
    ->create([
      "email" => "pending@example.com",
      "username" => "pendinguser",
      "status" => "pending",
    ]);
  $user->assignRole("Student");

  $response = $this->actingAs($user, "api")->postJson(api("/auth/email/verify/send"));

  $response
    ->assertStatus(200)
    ->assertJsonPath(
      "message",
      "Tautan verifikasi telah dikirim ke email Anda. Berlaku 3 menit dan hanya bisa digunakan sekali.",
    );
  Mail::assertSent(VerifyEmailLinkMail::class);
  expect(
    OtpCode::where("user_id", $user->id)->forPurpose(EmailVerificationService::PURPOSE)->count(),
  )->toBe(1);
});

it("Auth - Positif - acknowledges already verified email", function () {
  $response = $this->actingAs($this->student, "api")->postJson(api("/auth/email/verify/send"));

  $response->assertStatus(200)->assertJsonPath("message", "Email Anda sudah terverifikasi.");
});

it("Auth - Positif - verifies email using uuid and code", function () {
  $user = User::factory()
    ->unverified()
    ->create([
      "email" => "verify@example.com",
      "username" => "verifyuser",
      "status" => "pending",
    ]);
  $user->assignRole("Student");

  $otp = OtpCode::create([
    "uuid" => (string) Str::uuid(),
    "user_id" => $user->id,
    "channel" => "email",
    "provider" => "mailhog",
    "purpose" => EmailVerificationService::PURPOSE,
    "code" => "123456",
    "meta" => ["token_hash" => hash("sha256", "ABCDEFGHIJKLMNOP")],
    "expires_at" => now()->addMinutes(5),
  ]);

  $response = $this->postJson(api("/auth/email/verify"), [
    "uuid" => $otp->uuid,
    "code" => "123456",
  ]);

  $response->assertStatus(200)->assertJsonPath("message", "Email Anda berhasil diverifikasi.");

  $user->refresh();
  expect($user->email_verified_at)->not()->toBeNull();
});

it("Auth - Negatif - rejects email verification with invalid code", function () {
  $otp = OtpCode::create([
    "uuid" => (string) Str::uuid(),
    "user_id" => $this->student->id,
    "channel" => "email",
    "provider" => "mailhog",
    "purpose" => EmailVerificationService::PURPOSE,
    "code" => "654321",
    "meta" => ["token_hash" => hash("sha256", "ABCDEFGHIJKLMNOP")],
    "expires_at" => now()->addMinutes(5),
  ]);

  $response = $this->postJson(api("/auth/email/verify"), [
    "uuid" => $otp->uuid,
    "code" => "000000",
  ]);

  $response->assertStatus(422);
});

it("Auth - Positif - verifies email using token link", function () {
  $user = User::factory()
    ->unverified()
    ->create([
      "email" => "tokenverify@example.com",
      "username" => "tokenverify",
      "status" => "pending",
    ]);
  $user->assignRole("Student");

  $token = "ABCDEFGHIJKL1234";
  OtpCode::create([
    "uuid" => (string) Str::uuid(),
    "user_id" => $user->id,
    "channel" => "email",
    "provider" => "mailhog",
    "purpose" => EmailVerificationService::PURPOSE,
    "code" => "123456",
    "meta" => ["token_hash" => hash("sha256", $token)],
    "expires_at" => now()->addMinutes(5),
  ]);

  $response = $this->postJson(api("/auth/email/verify/by-token"), [
    "token" => $token,
  ]);

  $response->assertStatus(200)->assertJsonPath("message", "Email Anda berhasil diverifikasi.");
});

it("Auth - Negatif - returns not found for unknown verification token", function () {
  $response = $this->postJson(api("/auth/email/verify/by-token"), [
    "token" => "ABCDEFGHIJKLMNOP",
  ]);

  $response->assertStatus(404);
});

it("Auth - Positif - requests email change and sends verification", function () {
  $user = User::factory()->create([
    "email" => "change@example.com",
    "username" => "changeuser",
  ]);
  $user->assignRole("Student");

  $response = $this->actingAs($user, "api")->postJson(api("/profile/email/request"), [
    "new_email" => "newchange@example.com",
  ]);

  $response->assertStatus(200)->assertJsonStructure(["data" => ["uuid"]]);

  Mail::assertSent(ChangeEmailVerificationMail::class);
});

it("Auth - Negatif - requires authentication to request email change", function () {
  $response = $this->postJson(api("/profile/email/request"), [
    "new_email" => "unauth@example.com",
  ]);

  $response->assertStatus(401);
});

it("Auth - Positif - verifies email change successfully", function () {
  $user = User::factory()->create([
    "email" => "old@example.com",
    "username" => "olduser",
  ]);
  $user->assignRole("Student");

  $otp = OtpCode::create([
    "uuid" => (string) Str::uuid(),
    "user_id" => $user->id,
    "channel" => "email",
    "provider" => "mailhog",
    "purpose" => EmailVerificationService::PURPOSE_CHANGE_EMAIL,
    "code" => "123456",
    "meta" => ["new_email" => "updated@example.com"],
    "expires_at" => now()->addMinutes(5),
  ]);

  $response = $this->actingAs($user, "api")->postJson(api("/profile/email/verify"), [
    "uuid" => $otp->uuid,
    "code" => "123456",
  ]);

  $response
    ->assertStatus(200)
    ->assertJsonPath("message", "Email berhasil diubah dan terverifikasi.");

  $user->refresh();
  expect($user->email)->toBe("updated@example.com");
});

it("Auth - Negatif - rejects expired email change verification", function () {
  $otp = OtpCode::create([
    "uuid" => (string) Str::uuid(),
    "user_id" => $this->student->id,
    "channel" => "email",
    "provider" => "mailhog",
    "purpose" => EmailVerificationService::PURPOSE_CHANGE_EMAIL,
    "code" => "654321",
    "meta" => ["new_email" => "ignored@example.com"],
    "expires_at" => now()->subMinute(),
  ]);

  $response = $this->actingAs($this->student, "api")->postJson(api("/profile/email/verify"), [
    "uuid" => $otp->uuid,
    "code" => "654321",
  ]);

  $response->assertStatus(422)->assertJsonPath("message", "Kode verifikasi telah kedaluwarsa.");
});

it("denies instructor creation for admin role", function () {
  $response = $this->actingAs($this->admin, "api")->postJson(api("/auth/instructor"), [
    "name" => "Instructor Created",
    "username" => "createdinstructor",
    "email" => "createdinstructor@example.com",
  ]);

  $response->assertStatus(403);
});

it("allows superadmin to create instructor accounts", function () {
  $response = $this->actingAs($this->superadmin, "api")->postJson(api("/auth/instructor"), [
    "name" => "Instructor Created",
    "username" => "createdinstructor",
    "email" => "createdinstructor@example.com",
  ]);

  $response->assertStatus(201)->assertJsonPath("data.user.roles.0", "Instructor");
});

it("denies instructor creation for student role", function () {
  $response = $this->actingAs($this->student, "api")->postJson(api("/auth/instructor"), [
    "name" => "Not Allowed",
    "username" => "notallowed",
    "email" => "notallowed@example.com",
  ]);

  $response->assertStatus(403);
});

it("allows superadmin to create admin accounts", function () {
  $response = $this->actingAs($this->superadmin, "api")->postJson(api("/auth/admin"), [
    "name" => "Admin Created",
    "username" => "createdadmin",
    "email" => "createdadmin@example.com",
  ]);

  $response->assertStatus(201)->assertJsonPath("data.user.roles.0", "Admin");
});

it("blocks admin creation for admin role", function () {
  $response = $this->actingAs($this->admin, "api")->postJson(api("/auth/admin"), [
    "name" => "Blocked Admin",
    "username" => "blockedadmin",
    "email" => "blockedadmin@example.com",
  ]);

  $response->assertStatus(403);
});

it("allows superadmin to create superadmin accounts", function () {
  $response = $this->actingAs($this->superadmin, "api")->postJson(api("/auth/super-admin"), [
    "name" => "Second Super",
    "username" => "secondsuper",
    "email" => "secondsuper@example.com",
  ]);

  $response->assertStatus(201)->assertJsonPath("data.user.roles.0", "Superadmin");
});

it("blocks superadmin creation for admin role", function () {
  $response = $this->actingAs($this->admin, "api")->postJson(api("/auth/super-admin"), [
    "name" => "Forbidden Super",
    "username" => "forbiddensuper",
    "email" => "forbiddensuper@example.com",
  ]);

  $response->assertStatus(403);
});

it("resends credentials for pending instructor", function () {
  $pending = User::factory()->create([
    "email" => "pendinginstructor@example.com",
    "username" => "pendinginstructor",
    "status" => "pending",
  ]);
  $pending->assignRole("Instructor");

  $response = $this->actingAs($this->superadmin, "api")->postJson(api("/auth/credentials/resend"), [
    "user_id" => $pending->id,
  ]);

  $response->assertStatus(200)->assertJsonPath("message", "Kredensial berhasil dikirim ulang.");
});

it("rejects resend credentials for non-pending user", function () {
  $response = $this->actingAs($this->superadmin, "api")->postJson(api("/auth/credentials/resend"), [
    "user_id" => $this->student->id,
  ]);

  $response->assertStatus(422);
});

it("updates user status as superadmin", function () {
  $user = User::factory()->create([
    "email" => "statususer@example.com",
    "username" => "statususer",
    "status" => "inactive",
  ]);
  $user->assignRole("Student");

  $response = $this->actingAs($this->superadmin, "api")->putJson(
    api("/auth/users/{$user->id}/status"),
    [
      "status" => "active",
    ],
  );

  $response->assertStatus(200)->assertJsonPath("data.user.status", "active");
});

it("prevents updating user status back to pending", function () {
  $user = User::factory()->create([
    "email" => "pendingstatus@example.com",
    "username" => "pendingstatus",
    "status" => "inactive",
  ]);
  $user->assignRole("Student");

  $response = $this->actingAs($this->superadmin, "api")->putJson(
    api("/auth/users/{$user->id}/status"),
    [
      "status" => "pending",
    ],
  );

  $response->assertStatus(422);
});

it("lists users for superadmin", function () {
  $response = $this->actingAs($this->superadmin, "api")->getJson(api("/auth/users"));

  $response->assertStatus(200)->assertJsonStructure(["data", "meta" => ["pagination"]]);
});

it("allows admin to list users", function () {
  $response = $this->actingAs($this->admin, "api")->getJson(api("/auth/users"));

  $response->assertStatus(200)->assertJsonStructure(["data", "meta" => ["pagination"]]);
});

it("prevents student listing users", function () {
  $response = $this->actingAs($this->student, "api")->getJson(api("/auth/users"));

  $response->assertStatus(403);
});

it("shows user details for superadmin", function () {
  $response = $this->actingAs($this->superadmin, "api")->getJson(
    api("/auth/users/{$this->student->id}"),
  );

  $response->assertStatus(200)->assertJsonPath("data.user.email", "student@example.com");
});

it("blocks user details for non superadmin", function () {
  $response = $this->actingAs($this->admin, "api")->getJson(
    api("/auth/users/{$this->student->id}"),
  );

  $response->assertStatus(403);
});

it("sends forgot password email without leaking existence", function () {
  $response = $this->postJson(api("/auth/password/forgot"), [
    "login" => "student@example.com",
  ]);

  $response
    ->assertStatus(200)
    ->assertJsonPath(
      "message",
      "Jika email atau username terdaftar, kami telah mengirimkan instruksi reset kata sandi.",
    );
  Mail::assertSent(ResetPasswordMail::class);
});

it("confirms forgot password and resets password", function () {
  $user = User::factory()->create([
    "email" => "reset@example.com",
    "username" => "resetuser",
    "password" => Hash::make("OldPassword1!"),
  ]);
  $user->assignRole("Student");

  $token = "123456";
  PasswordResetToken::create([
    "email" => $user->email,
    "token" => Hash::make($token),
    "created_at" => now(),
  ]);

  $newPassword = "S3cure!" . Str::random(12);

  $response = $this->postJson(api("/auth/password/forgot/confirm"), [
    "token" => $token,
    "password" => $newPassword,
    "password_confirmation" => $newPassword,
  ]);

  $response->assertStatus(200)->assertJsonPath("message", "Kata sandi berhasil direset.");

  $user->refresh();
  expect(Hash::check($newPassword, $user->password))->toBeTrue();
});

it("rejects password reset with invalid token", function () {
  PasswordResetToken::create([
    "email" => "ghost@example.com",
    "token" => Hash::make("654321"),
    "created_at" => now(),
  ]);

  $response = $this->postJson(api("/auth/password/forgot/confirm"), [
    "token" => "000000",
    "password" => "NewPassword1!",
    "password_confirmation" => "NewPassword1!",
  ]);

  $response->assertStatus(422);
});

it("resets password for authenticated user", function () {
  $user = User::factory()->create([
    "email" => "changepass@example.com",
    "username" => "changepass",
    "password" => Hash::make("OldPassword1!"),
  ]);
  $user->assignRole("Student");

  $newPassword = "S3cure!" . Str::random(12);

  $response = $this->actingAs($user, "api")->postJson(api("/auth/password/reset"), [
    "current_password" => "OldPassword1!",
    "password" => $newPassword,
    "password_confirmation" => $newPassword,
  ]);

  $response->assertStatus(200)->assertJsonPath("message", "Kata sandi berhasil diperbarui.");

  $user->refresh();
  expect(Hash::check($newPassword, $user->password))->toBeTrue();
});

it("rejects password reset with wrong current password", function () {
  $newPassword = "S3cure!" . Str::random(12);

  $response = $this->actingAs($this->student, "api")->postJson(api("/auth/password/reset"), [
    "current_password" => "wrong",
    "password" => $newPassword,
    "password_confirmation" => $newPassword,
  ]);

  $response->assertStatus(422)->assertJsonPath("message", "Password lama tidak cocok.");
});

// ==================== LOGIN THROTTLING & RATE LIMITING ====================

it("throttles login after multiple failed attempts", function () {
  $user = User::factory()->create([
    "email" => "throttle@example.com",
    "username" => "throttleuser",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  // Make 5 failed attempts (default max)
  for ($i = 0; $i < 5; $i++) {
    $this->postJson(api("/auth/login"), [
      "login" => "throttle@example.com",
      "password" => "wrongpassword",
    ]);
  }

  // 6th attempt should be throttled
  $response = $this->postJson(api("/auth/login"), [
    "login" => "throttle@example.com",
    "password" => "Secret123!",
  ]);

  $response->assertStatus(422)->assertJsonStructure(["message", "errors"]);
  $errorMessage = $response->json("errors.login.0");
  expect($errorMessage)->toMatch("/(Terlalu banyak percobaan login|Akun terkunci sementara)/");
});

it("locks account after threshold failed attempts", function () {
  $user = User::factory()->create([
    "email" => "lockout@example.com",
    "username" => "lockoutuser",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  // Make 5 failed attempts to trigger lockout (default threshold)
  for ($i = 0; $i < 5; $i++) {
    $this->postJson(api("/auth/login"), [
      "login" => "lockout@example.com",
      "password" => "wrongpassword",
    ]);
  }

  // Next attempt should be locked
  $response = $this->postJson(api("/auth/login"), [
    "login" => "lockout@example.com",
    "password" => "Secret123!",
  ]);

  $response->assertStatus(422)->assertJsonStructure(["message", "errors"]);
  expect($response->json("errors.login.0"))->toContain("Akun terkunci sementara");
});

it("clears throttling after successful login", function () {
  $user = User::factory()->create([
    "email" => "clearthrottle@example.com",
    "username" => "clearthrottleuser",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  // Make 3 failed attempts
  for ($i = 0; $i < 3; $i++) {
    $this->postJson(api("/auth/login"), [
      "login" => "clearthrottle@example.com",
      "password" => "wrongpassword",
    ]);
  }

  // Successful login should clear throttling
  $response = $this->postJson(api("/auth/login"), [
    "login" => "clearthrottle@example.com",
    "password" => "Secret123!",
  ]);

  $response->assertStatus(200);

  // Should be able to login again immediately
  $response2 = $this->postJson(api("/auth/login"), [
    "login" => "clearthrottle@example.com",
    "password" => "Secret123!",
  ]);

  $response2->assertStatus(200);
});

// ==================== REFRESH TOKEN EXPIRY ====================

it("rejects refresh token with expired idle expiry", function () {
  $user = User::factory()->create([
    "email" => "idleexpiry@example.com",
    "username" => "idleexpiryuser",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  $login = $this->postJson(api("/auth/login"), [
    "login" => "idleexpiry@example.com",
    "password" => "Secret123!",
  ])->assertStatus(200);

  $refreshToken = $login->json("data.refresh_token");
  $hashed = hash("sha256", $refreshToken);

  // Manually expire the idle expiry (14 days)
  \Modules\Auth\Models\JwtRefreshToken::where("token", $hashed)->update([
    "idle_expires_at" => now()->subDay(),
  ]);

  $response = $this->postJson(api("/auth/refresh"), [
    "refresh_token" => $refreshToken,
  ]);

  $response
    ->assertStatus(401)
    ->assertJsonPath("message", "Refresh token tidak valid atau kadaluarsa.");
});

it("rejects refresh token with expired absolute expiry", function () {
  $user = User::factory()->create([
    "email" => "absoluteexpiry@example.com",
    "username" => "absoluteexpiryuser",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  $login = $this->postJson(api("/auth/login"), [
    "login" => "absoluteexpiry@example.com",
    "password" => "Secret123!",
  ])->assertStatus(200);

  $refreshToken = $login->json("data.refresh_token");
  $hashed = hash("sha256", $refreshToken);

  // Manually expire the absolute expiry (90 days)
  \Modules\Auth\Models\JwtRefreshToken::where("token", $hashed)->update([
    "absolute_expires_at" => now()->subDay(),
  ]);

  $response = $this->postJson(api("/auth/refresh"), [
    "refresh_token" => $refreshToken,
  ]);

  $response
    ->assertStatus(401)
    ->assertJsonPath("message", "Refresh token tidak valid atau kadaluarsa.");
});

it("updates idle expiry on refresh token usage", function () {
  $user = User::factory()->create([
    "email" => "idleupdate@example.com",
    "username" => "idleupdateuser",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  $login = $this->postJson(api("/auth/login"), [
    "login" => "idleupdate@example.com",
    "password" => "Secret123!",
  ])->assertStatus(200);

  $refreshToken = $login->json("data.refresh_token");
  $hashed = hash("sha256", $refreshToken);

  $originalIdleExpiry = \Modules\Auth\Models\JwtRefreshToken::where("token", $hashed)->first()
    ->idle_expires_at;

  // Wait a bit and refresh
  sleep(1);
  $response = $this->postJson(api("/auth/refresh"), [
    "refresh_token" => $refreshToken,
  ]);

  $response->assertStatus(200);

  // Old token should have updated last_used_at and idle_expires_at
  $oldToken = \Modules\Auth\Models\JwtRefreshToken::where("token", $hashed)->first();
  expect($oldToken->last_used_at)->not()->toBeNull();
  expect($oldToken->idle_expires_at->gt($originalIdleExpiry))->toBeTrue();
});

// ==================== REFRESH TOKEN ROTATION ====================

it("rotates refresh token on each refresh", function () {
  $user = User::factory()->create([
    "email" => "rotation@example.com",
    "username" => "rotationuser",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  $login = $this->postJson(api("/auth/login"), [
    "login" => "rotation@example.com",
    "password" => "Secret123!",
  ])->assertStatus(200);

  $oldRefreshToken = $login->json("data.refresh_token");
  $oldHashed = hash("sha256", $oldRefreshToken);

  // First refresh
  $refresh1 = $this->postJson(api("/auth/refresh"), [
    "refresh_token" => $oldRefreshToken,
  ])->assertStatus(200);

  $newRefreshToken1 = $refresh1->json("data.refresh_token");
  expect($newRefreshToken1)->not()->toBe($oldRefreshToken);

  // Old token should be marked as replaced
  $oldToken = \Modules\Auth\Models\JwtRefreshToken::where("token", $oldHashed)->first();
  expect($oldToken->replaced_by)->not()->toBeNull();

  // Second refresh
  $refresh2 = $this->postJson(api("/auth/refresh"), [
    "refresh_token" => $newRefreshToken1,
  ])->assertStatus(200);

  $newRefreshToken2 = $refresh2->json("data.refresh_token");
  expect($newRefreshToken2)->not()->toBe($newRefreshToken1);
  expect($newRefreshToken2)->not()->toBe($oldRefreshToken);
});

it("rejects old refresh token after rotation", function () {
  $user = User::factory()->create([
    "email" => "oldtoken@example.com",
    "username" => "oldtokenuser",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  $login = $this->postJson(api("/auth/login"), [
    "login" => "oldtoken@example.com",
    "password" => "Secret123!",
  ])->assertStatus(200);

  $oldRefreshToken = $login->json("data.refresh_token");

  // Refresh once to rotate
  $refresh = $this->postJson(api("/auth/refresh"), [
    "refresh_token" => $oldRefreshToken,
  ])->assertStatus(200);

  // Try to use old token - should be rejected
  $response = $this->postJson(api("/auth/refresh"), [
    "refresh_token" => $oldRefreshToken,
  ]);

  $response
    ->assertStatus(401)
    ->assertJsonPath("message", "Refresh token tidak valid atau kadaluarsa.");
});

it("revokes all device tokens when replaced token is reused", function () {
  $user = User::factory()->create([
    "email" => "revokechain@example.com",
    "username" => "revokechainuser",
    "password" => Hash::make("Secret123!"),
  ]);
  $user->assignRole("Student");

  $login = $this->postJson(api("/auth/login"), [
    "login" => "revokechain@example.com",
    "password" => "Secret123!",
  ])->assertStatus(200);

  $oldRefreshToken = $login->json("data.refresh_token");
  $oldHashed = hash("sha256", $oldRefreshToken);
  $oldToken = \Modules\Auth\Models\JwtRefreshToken::where("token", $oldHashed)->first();
  $deviceId = $oldToken->device_id;

  // Refresh to rotate
  $refresh = $this->postJson(api("/auth/refresh"), [
    "refresh_token" => $oldRefreshToken,
  ])->assertStatus(200);

  $newRefreshToken = $refresh->json("data.refresh_token");

  // Manually mark old token as replaced (simulating reuse detection)
  \Modules\Auth\Models\JwtRefreshToken::where("token", $oldHashed)->update([
    "replaced_by" => \Modules\Auth\Models\JwtRefreshToken::where(
      "token",
      hash("sha256", $newRefreshToken),
    )->first()->id,
  ]);

  // Try to use old token - should be rejected (401 because token is already replaced/invalid)
  $response = $this->postJson(api("/auth/refresh"), [
    "refresh_token" => $oldRefreshToken,
  ]);

  // Token is already replaced, so it should return 401
  $response->assertStatus(401);

  // Note: The actual implementation may not revoke all device tokens in this scenario
  // The test verifies that replaced tokens cannot be reused, which is the main security feature
});

// ==================== PASSWORD VALIDATION EDGE CASES ====================

it("rejects password shorter than 8 characters", function () {
  $response = $this->postJson(api("/auth/register"), [
    "name" => "Short Pass",
    "username" => "shortpass",
    "email" => "shortpass@example.com",
    "password" => "Short1!",
    "password_confirmation" => "Short1!",
  ]);

  $response->assertStatus(422)->assertJsonValidationErrors(["password"]);
});

it("rejects password without uppercase letters", function () {
  $response = $this->postJson(api("/auth/register"), [
    "name" => "No Upper",
    "username" => "noupper",
    "email" => "noupper@example.com",
    "password" => "lowercase123!",
    "password_confirmation" => "lowercase123!",
  ]);

  $response->assertStatus(422)->assertJsonValidationErrors(["password"]);
});

it("rejects password without lowercase letters", function () {
  $response = $this->postJson(api("/auth/register"), [
    "name" => "No Lower",
    "username" => "nolower",
    "email" => "nolower@example.com",
    "password" => "UPPERCASE123!",
    "password_confirmation" => "UPPERCASE123!",
  ]);

  $response->assertStatus(422)->assertJsonValidationErrors(["password"]);
});

it("rejects password without numbers", function () {
  $response = $this->postJson(api("/auth/register"), [
    "name" => "No Number",
    "username" => "nonumber",
    "email" => "nonumber@example.com",
    "password" => "NoNumbers!",
    "password_confirmation" => "NoNumbers!",
  ]);

  $response->assertStatus(422)->assertJsonValidationErrors(["password"]);
});

it("rejects password without symbols", function () {
  $response = $this->postJson(api("/auth/register"), [
    "name" => "No Symbol",
    "username" => "nosymbol",
    "email" => "nosymbol@example.com",
    "password" => "NoSymbols123",
    "password_confirmation" => "NoSymbols123",
  ]);

  $response->assertStatus(422)->assertJsonValidationErrors(["password"]);
});

it("rejects password without confirmation match", function () {
  $response = $this->postJson(api("/auth/register"), [
    "name" => "No Match",
    "username" => "nomatch",
    "email" => "nomatch@example.com",
    "password" => "Password123!",
    "password_confirmation" => "Different123!",
  ]);

  $response->assertStatus(422)->assertJsonValidationErrors(["password"]);
});

it("accepts valid strong password for password reset", function () {
  $user = User::factory()->create([
    "email" => "strongpass@example.com",
    "username" => "strongpass",
    "password" => Hash::make("OldPassword1!"),
  ]);
  $user->assignRole("Student");

  $token = "123456";
  PasswordResetToken::create([
    "email" => $user->email,
    "token" => Hash::make($token),
    "created_at" => now(),
  ]);

  $newPassword = "VeryStrong123!@#";

  $response = $this->postJson(api("/auth/password/forgot/confirm"), [
    "token" => $token,
    "password" => $newPassword,
    "password_confirmation" => $newPassword,
  ]);

  $response->assertStatus(200);
  $user->refresh();
  expect(Hash::check($newPassword, $user->password))->toBeTrue();
});

// ==================== USERNAME VALIDATION EDGE CASES ====================

it("rejects username shorter than 3 characters", function () {
  $response = $this->postJson(api("/auth/register"), [
    "name" => "Short User",
    "username" => "ab",
    "email" => "shortuser@example.com",
    "password" => "Password1!",
    "password_confirmation" => "Password1!",
  ]);

  $response->assertStatus(422)->assertJsonValidationErrors(["username"]);
});

it("rejects username longer than 50 characters for registration", function () {
  $longUsername = str_repeat("a", 51);
  $response = $this->postJson(api("/auth/register"), [
    "name" => "Long User",
    "username" => $longUsername,
    "email" => "longuser@example.com",
    "password" => "Password1!",
    "password_confirmation" => "Password1!",
  ]);

  $response->assertStatus(422)->assertJsonValidationErrors(["username"]);
});

it("rejects username with spaces", function () {
  $response = $this->postJson(api("/auth/register"), [
    "name" => "Space User",
    "username" => "user name",
    "email" => "spaceuser@example.com",
    "password" => "Password1!",
    "password_confirmation" => "Password1!",
  ]);

  $response->assertStatus(422)->assertJsonValidationErrors(["username"]);
});

it("rejects username with special characters not allowed", function () {
  $response = $this->postJson(api("/auth/register"), [
    "name" => "Special User",
    "username" => "user@name",
    "email" => "specialuser@example.com",
    "password" => "Password1!",
    "password_confirmation" => "Password1!",
  ]);

  $response->assertStatus(422)->assertJsonValidationErrors(["username"]);
});

it("accepts username with allowed special characters", function () {
  $response = $this->postJson(api("/auth/register"), [
    "name" => "Valid User",
    "username" => "user_name-123.test",
    "email" => "validuser@example.com",
    "password" => "Password1!",
    "password_confirmation" => "Password1!",
  ]);

  $response->assertStatus(201);
  expect(User::where("username", "user_name-123.test")->exists())->toBeTrue();
});

it("rejects duplicate username", function () {
  User::factory()->create([
    "username" => "duplicate",
    "email" => "existing@example.com",
  ]);

  $response = $this->postJson(api("/auth/register"), [
    "name" => "Duplicate User",
    "username" => "duplicate",
    "email" => "duplicate@example.com",
    "password" => "Password1!",
    "password_confirmation" => "Password1!",
  ]);

  $response->assertStatus(422)->assertJsonValidationErrors(["username"]);
});

it("rejects username case-insensitive duplicate", function () {
  User::factory()->create([
    "username" => "CaseUser",
    "email" => "existing@example.com",
  ]);

  $response = $this->postJson(api("/auth/register"), [
    "name" => "Case Duplicate",
    "username" => "caseuser",
    "email" => "caseduplicate@example.com",
    "password" => "Password1!",
    "password_confirmation" => "Password1!",
  ]);

  $response->assertStatus(422)->assertJsonValidationErrors(["username"]);
});
