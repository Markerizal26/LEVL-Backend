<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\AbstractProvider as SocialiteAbstractProvider;
use Modules\Auth\Contracts\AuthRepositoryInterface;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\DTOs\RegisterDTO;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\LogoutRequest;
use Modules\Auth\Http\Requests\RefreshTokenRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Requests\RequestEmailChangeRequest;
use Modules\Auth\Http\Requests\ResendCredentialsRequest;
use Modules\Auth\Http\Requests\SetUsernameRequest;
use Modules\Auth\Http\Requests\UpdateProfileRequest;
use Modules\Auth\Http\Requests\UpdateUserStatusRequest;
use Modules\Auth\Http\Requests\VerifyEmailByTokenRequest;
use Modules\Auth\Http\Requests\VerifyEmailChangeRequest;
use Modules\Auth\Http\Requests\VerifyEmailRequest;
use Modules\Auth\Models\SocialAccount;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\EmailVerificationService;
use Tymon\JWTAuth\JWTAuth;

/**
 * @tags Autentikasi
 */
class AuthApiController extends Controller
{
  use ApiResponse;

  public function __construct(
    private readonly AuthService $auth,
    private readonly EmailVerificationService $emailVerification,
  ) {}

  /**
   * Registrasi
   *
   * Mendaftarkan pengguna baru dengan role Student. Setelah registrasi berhasil, email verifikasi akan dikirim ke alamat email yang didaftarkan.
   *
   *
   * @summary Registrasi
   *
   * @bodyParam name string required Nama lengkap pengguna. Example: John Doe
   * @bodyParam email string required Email valid yang belum terdaftar. Example: john@example.com
   * @bodyParam username string required Username unik (3-20 karakter, alphanumeric dan underscore). Example: johndoe
   * @bodyParam password string required Password minimal 8 karakter. Example: SecurePass123
   * @bodyParam password_confirmation string required Konfirmasi password (harus sama dengan password). Example: SecurePass123
   *
   * @response 201 scenario="Success" {"success": true, "message": "Registrasi berhasil. Silakan periksa email Anda untuk verifikasi.", "data": {"user": {"id": 1, "name": "John Doe", "email": "john@example.com", "username": "johndoe", "status": "pending"}, "verification_uuid": "550e8400-e29b-41d4-a716-446655440000"}}
   * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"email": ["Email sudah terdaftar."], "username": ["Username sudah digunakan."]}}
   * @response 429 scenario="Rate Limited" {"success":false,"message":"Terlalu banyak percobaan. Silakan coba lagi dalam 60 detik."}
   *
   * @unauthenticated
   */
  public function register(RegisterRequest $request): JsonResponse
  {
    $dto = RegisterDTO::fromRequest($request->validated());

    $data = $this->auth->register(data: $dto, ip: $request->ip(), userAgent: $request->userAgent());

    return $this->created(
      $data,
      "Registrasi berhasil. Silakan periksa email Anda untuk verifikasi.",
    );
  }

  /**
   * Login
   *
   * Autentikasi pengguna menggunakan email/username dan password. Mengembalikan access token (JWT) dan refresh token untuk sesi.
   *
   *
   * @summary Login
   *
   * @bodyParam login string required Email atau username. Example: john@example.com
   * @bodyParam password string required Password pengguna. Example: SecurePass123
   *
   * @response 200 scenario="Success" {"success": true, "message": "Login berhasil.", "data": {"user": {"id": 1, "name": "John Doe", "email": "john@example.com", "status": "active"}, "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...", "refresh_token": "abc123def456...", "expires_in": 900}}
   * @response 401 scenario="Invalid Credentials" {"success":false,"message":"Email atau password salah."}
   * @response 403 scenario="Account Inactive" {"success":false,"message":"Akun Anda tidak aktif. Silakan hubungi administrator."}
   * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"login": ["Field login wajib diisi."], "password": ["Field password wajib diisi."]}}
   * @response 429 scenario="Rate Limited" {"success":false,"message":"Terlalu banyak percobaan. Silakan coba lagi dalam 60 detik."}
   *
   * @unauthenticated
   */
  public function login(LoginRequest $request): JsonResponse
  {
    $dto = LoginDTO::fromRequest($request->validated());

    $data = $this->auth->login(
      loginOrDto: $dto,
      password: null,
      ip: $request->ip(),
      userAgent: $request->userAgent(),
    );

    if (isset($data["message"])) {
      return $this->success($data, $data["message"]);
    }

    return $this->success($data, "Login berhasil.");
  }

  /**
   * Buat Akun Instructor
   *
   * Membuat akun instructor baru. Password akan di-generate otomatis dan dikirim ke email. **Memerlukan role: Superadmin**
   *
   *
   * @summary Buat Akun Instructor
   *
   * @bodyParam name string required Nama lengkap instructor. Example: Jane Instructor
   * @bodyParam email string required Email instructor. Example: jane@example.com
   *
   * @response 201 scenario="Success" {"success": true, "message": "Instructor berhasil dibuat.", "data": {"user": {"id": 2, "name": "Jane Instructor", "email": "jane@example.com", "status": "pending"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk melakukan aksi ini."}
   * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"email": ["Email sudah terdaftar."]}}
   *
   * @authenticated
   *
   * @role Superadmin
   *
   * @bodyParam name string required Nama lengkap admin. Example: Admin User
   * @bodyParam email string required Email admin. Example: admin@example.com
   *
   * @response 201 scenario="Success" {"success": true, "message": "Admin berhasil dibuat.", "data": {"user": {"id": 3, "name": "Admin User", "email": "admin@example.com", "status": "pending"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk melakukan aksi ini."}
   * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"email": ["Email sudah terdaftar."]}}
   *
   * @authenticated
   *
   * @role Superadmin
   *
   * @bodyParam name string required Nama lengkap super admin. Example: Super Admin
   * @bodyParam email string required Email super admin. Example: superadmin@example.com
   *
   * @response 201 scenario="Success" {"success": true, "message": "Super admin berhasil dibuat.", "data": {"user": {"id": 4, "name": "Super Admin", "email": "superadmin@example.com", "status": "pending"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk melakukan aksi ini."}
   * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"email": ["Email sudah terdaftar."]}}
   *
   * @authenticated
   *
   * @role Superadmin
   *
   * @description Memperbarui access token menggunakan refresh token yang valid. Refresh token dapat dikirim via cookie (refresh_token), header (X-Refresh-Token), atau body (refresh_token). Endpoint ini tidak memerlukan access token dan dapat digunakan untuk mobile app.
   *
   * **Durasi Token:**
   * - Access Token: 15 menit (dapat dikonfigurasi via JWT_TTL)
   * - Refresh Token:
   *   - Idle Expiry: 14 hari (token akan expired jika tidak digunakan selama 14 hari)
   *   - Absolute Expiry: 90 hari (token akan expired setelah 90 hari terlepas dari penggunaan)
   *
   * **Cara Penggunaan:**
   *
   * **Frontend (Web):**
   * - Kirim refresh token via cookie `refresh_token` (httpOnly, secure, sameSite)
   * - Atau via header `X-Refresh-Token`
   * - Atau via body `refresh_token`
   * - Contoh: `POST /api/v1/auth/refresh` dengan header `X-Refresh-Token: <refresh_token>`
   *
   * **Mobile App:**
   * - Kirim refresh token via header `X-Refresh-Token` (disarankan)
   * - Atau via body `refresh_token`
   * - Simpan refresh token di secure storage (Keychain/Keystore)
   * - Contoh: `POST /api/v1/auth/refresh` dengan header `X-Refresh-Token: <refresh_token>`
   *
   * **Response:**
   * - Mengembalikan access token baru, refresh token baru (rotating), dan expires_in (dalam detik)
   * - Refresh token lama akan di-revoke dan diganti dengan yang baru (token rotation)
   *
   * @bodyParam refresh_token string required Refresh token yang valid. Example: abc123def456...
   *
   * @response 200 scenario="Success" {"success": true, "message": "Token akses berhasil diperbarui.", "data": {"access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...", "refresh_token": "newtoken123...", "expires_in": 900}}
   * @response 401 scenario="Invalid Token" {"success":false,"message":"Refresh token tidak valid atau kadaluarsa."}
   *
   * @unauthenticated
   */
  public function refresh(RefreshTokenRequest $request): JsonResponse
  {
    try {
      $refreshToken = $request->string("refresh_token");
      $data = $this->auth->refresh($refreshToken, $request->ip(), $request->userAgent());
    } catch (ValidationException $e) {
      return $this->error("Refresh token tidak valid atau kadaluarsa.", 401);
    }

    return $this->success($data, "Token akses berhasil diperbarui.");
  }

  /**
   * Logout
   *
   * Logout pengguna dan invalidate access token serta refresh token.
   *
   *
   * @summary Logout
   *
   * @bodyParam refresh_token string optional Refresh token untuk di-invalidate. Example: abc123def456...
   *
   * @response 200 scenario="Success" {"success":true,"message":"Logout berhasil.","data":[]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function logout(LogoutRequest $request): JsonResponse
  {
    /** @var \Modules\Auth\Models\User|null $user */
    $user = auth("api")->user();
    if (!$user) {
      return $this->error("Tidak terotorisasi.", 401);
    }

    $currentJwt = $request->bearerToken();
    if (!$currentJwt) {
      return $this->error("Tidak terotorisasi.", 401);
    }

    $this->auth->logout($user, $currentJwt, $request->input("refresh_token"));

    return $this->success([], "Logout berhasil.");
  }

  /**
   * Ambil Profil
   *
   * Mengambil data profil pengguna yang sedang login.
   *
   *
   * @summary Ambil Profil
   *
   * @response 200 scenario="Success" {"success": true, "message": "Profil berhasil diambil.", "data": {"id": 1, "name": "John Doe", "email": "john@example.com", "username": "johndoe", "status": "active", "avatar_url": "https://example.com/avatar.jpg"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function profile(): JsonResponse
  {
    /** @var \Modules\Auth\Models\User|null $user */
    $user = auth("api")->user();
    if (!$user) {
      return $this->error("Tidak terotorisasi.", 401);
    }

    return $this->success($user->toArray(), "Profil berhasil diambil.");
  }

  /**
   * Perbarui Profil
   *
   * Memperbarui data profil pengguna yang sedang login (nama, username, avatar).
   *
   *
   * @summary Perbarui Profil
   *
   * @bodyParam name string optional Nama lengkap baru. Example: John Updated
   * @bodyParam username string optional Username baru (harus unik). Example: johnupdated
   * @bodyParam avatar file optional File gambar avatar (jpg, png, max 2MB). Example: avatar.jpg
   *
   * @response 200 scenario="Success" {"success": true, "message": "Profil berhasil diperbarui.", "data": {"id": 1, "name": "John Updated", "email": "john@example.com", "username": "johnupdated", "status": "active"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"username": ["Username sudah digunakan."]}}
   *
   * @authenticated
   */
  public function updateProfile(UpdateProfileRequest $request): JsonResponse
  {
    /** @var \Modules\Auth\Models\User|null $user */
    $user = auth("api")->user();
    if (!$user) {
      return $this->error("Tidak terotorisasi.", 401);
    }

    $validated = $request->validated();

    $changes = [];
    if ($user->name !== $validated["name"]) {
      $changes["name"] = [$user->name, $validated["name"]];
      $user->name = $validated["name"];
    }
    if ($user->username !== $validated["username"]) {
      $changes["username"] = [$user->username, $validated["username"]];
      $user->username = $validated["username"];
    }

    // Handle avatar upload via Spatie Media Library
    if ($request->hasFile("avatar")) {
      $oldUrl = $user->avatar_url;
      $user->clearMediaCollection("avatar");
      $user->addMedia($request->file("avatar"))->toMediaCollection("avatar");
      $changes["avatar"] = [$oldUrl, $user->fresh()->avatar_url];
    }

    $user->save();

    $this->auth->logProfileUpdate($user, $changes, $request->ip(), $request->userAgent());

    return $this->success($user->fresh()->toArray(), "Profil berhasil diperbarui.");
  }

  /**
   * Redirect ke Google OAuth
   *
   *
   * @summary Redirect ke Google OAuth
   *
   * @description Mengarahkan pengguna ke halaman login Google untuk autentikasi OAuth.
   *
   * @response 302 scenario="Redirect" Redirect ke Google OAuth
   * @response 400 scenario="Error" {"success":false,"message":"Tidak dapat menginisiasi Google OAuth. Silakan login manual."}
   *
   * @unauthenticated
   */
  public function googleRedirect(Request $request)
  {
    try {
      /** @var SocialiteFactory $socialite */
      $socialite = app(SocialiteFactory::class);
      $provider = $socialite->driver("google");
      /** @var SocialiteAbstractProvider $provider */
      $provider = $provider->stateless();
      $redirectResponse = $provider->redirect();

      return $redirectResponse;
    } catch (\Throwable $e) {
      return $this->error("Tidak dapat menginisiasi Google OAuth. Silakan login manual.", 400);
    }
  }

  /**
   * Callback dari Google OAuth
   *
   *
   * @summary Callback dari Google OAuth
   *
   * @description Endpoint callback yang dipanggil oleh Google setelah autentikasi berhasil. Akan redirect ke frontend dengan token.
   *
   * @response 302 scenario="Success" Redirect ke frontend dengan access_token dan refresh_token
   * @response 302 scenario="Error" Redirect ke frontend dengan error parameter
   *
   * @unauthenticated
   */
  public function googleCallback(Request $request): RedirectResponse
  {
    $frontendUrl = env("FRONTEND_URL", "http://localhost:3000");
    $errorUrl = $frontendUrl . "/auth/login?error=google_login_failed";

    try {
      /** @var SocialiteFactory $socialite */
      $socialite = app(SocialiteFactory::class);
      $provider = $socialite->driver("google");
      /** @var SocialiteAbstractProvider $provider */
      $provider = $provider->stateless();
      $googleUser = $provider->user();
    } catch (\Throwable $e) {
      return redirect($errorUrl);
    }

    $email = $googleUser->getEmail();
    $name = $googleUser->getName() ?: $googleUser->user["given_name"] ?? "Google User";
    $providerId = $googleUser->getId();
    $provider = "google";

    // Find existing user by email or create a new one
    $user = User::query()->where("email", $email)->first();
    $isNewUser = !$user;
    if ($isNewUser) {
      $user = User::query()->create([
        "name" => $name,
        "username" => null,
        "email" => $email,
        // random password; not used for social login
        "password" => \Illuminate\Support\Str::random(32),
        "status" => UserStatus::Active->value,
        "email_verified_at" => now(),
      ]);
    }

    $account = SocialAccount::query()->firstOrNew([
      "provider_name" => $provider,
      "provider_id" => $providerId,
    ]);
    $account->user_id = $user->id;
    $account->token = $googleUser->token ?? null;
    $account->refresh_token = $googleUser->refreshToken ?? null;
    $account->save();

    /** @var JWTAuth $jwt */
    $jwt = app(JWTAuth::class);
    $accessToken = $jwt->fromUser($user);

    /** @var AuthRepositoryInterface $authRepo */
    $authRepo = app(AuthRepositoryInterface::class);
    $deviceId = hash("sha256", ($request->ip() ?? "") . ($request->userAgent() ?? "") . $user->id);
    $refresh = $authRepo->createRefreshToken(
      userId: $user->id,
      ip: $request->ip(),
      userAgent: $request->userAgent(),
      deviceId: $deviceId,
    );

    // Redirect to frontend with tokens in hash fragment (more secure, not sent to server)
    $successUrl =
      $frontendUrl .
      "/auth/callback?" .
      http_build_query([
        "access_token" => $accessToken,
        "refresh_token" => $refresh->getAttribute("plain_token"),
        "expires_in" => $jwt->factory()->getTTL() * 60,
        "provider" => $provider,
        "needs_username" => $isNewUser && !$user->username ? "1" : "0",
      ]);

    return redirect($successUrl);
  }

  /**
   * Kirim Tautan Verifikasi Email
   *
   *
   * @summary Kirim Tautan Verifikasi Email
   *
   * @description Mengirim ulang tautan verifikasi email ke pengguna yang belum terverifikasi.
   *
   * @response 200 scenario="Success" {"success": true, "message": "Tautan verifikasi telah dikirim ke email Anda. Berlaku 3 menit dan hanya bisa digunakan sekali.", "data": {"uuid": "550e8400-e29b-41d4-a716-446655440000"}}
   * @response 200 scenario="Already Verified" {"success":true,"message":"Email Anda sudah terverifikasi.","data":[]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function sendEmailVerification(Request $request): JsonResponse
  {
    /** @var \Modules\Auth\Models\User|null $user */
    $user = auth("api")->user();
    if (!$user) {
      return $this->error("Tidak terotorisasi.", 401);
    }

    if ($user->email_verified_at && $user->status === UserStatus::Active) {
      return $this->success([], "Email Anda sudah terverifikasi.");
    }

    $uuid = $this->emailVerification->sendVerificationLink($user);
    if ($uuid === null) {
      return $this->success([], "Email Anda sudah terverifikasi.");
    }

    return $this->success(
      ["uuid" => $uuid],
      "Tautan verifikasi telah dikirim ke email Anda. Berlaku 3 menit dan hanya bisa digunakan sekali.",
    );
  }

  /**
   * Minta Perubahan Email
   *
   *
   * @summary Minta Perubahan Email
   *
   * @description Meminta perubahan alamat email. Kode verifikasi akan dikirim ke email baru.
   *
   * @response 200 scenario="Success" {"success": true, "message": "Tautan verifikasi perubahan email telah dikirim. Berlaku 3 menit.", "data": {"uuid": "550e8400-e29b-41d4-a716-446655440000"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"new_email": ["Email sudah digunakan."]}}
   *
   * @authenticated
   */
  public function requestEmailChange(RequestEmailChangeRequest $request): JsonResponse
  {
    /** @var \Modules\Auth\Models\User|null $user */
    $user = auth("api")->user();
    if (!$user) {
      return $this->error("Tidak terotorisasi.", 401);
    }

    $validated = $request->validated();

    $uuid = $this->emailVerification->sendChangeEmailLink($user, $validated["new_email"]);

    $this->auth->logEmailChangeRequest(
      $user,
      $validated["new_email"],
      $uuid,
      $request->ip(),
      $request->userAgent(),
    );

    return $this->success(
      ["uuid" => $uuid],
      "Tautan verifikasi perubahan email telah dikirim. Berlaku 3 menit.",
    );
  }

  /**
   * Verifikasi Perubahan Email
   *
   *
   * @summary Verifikasi Perubahan Email
   *
   * @description Memverifikasi perubahan email menggunakan UUID dan kode OTP.
   *
   * @response 200 scenario="Success" {"success":true,"message":"Email berhasil diubah dan terverifikasi.","data":[]}
   * @response 404 scenario="Not Found" {"success":false,"message":"Tautan verifikasi tidak ditemukan."}
   * @response 422 scenario="Expired" {"success":false,"message":"Kode verifikasi telah kedaluwarsa."}
   * @response 422 scenario="Invalid" {"success":false,"message":"Kode verifikasi salah."}
   * @response 422 scenario="Email Taken" {"success":false,"message":"Email sudah digunakan oleh akun lain."}
   *
   * @authenticated
   */
  public function verifyEmailChange(VerifyEmailChangeRequest $request): JsonResponse
  {
    $validated = $request->validated();

    $result = $this->emailVerification->verifyChangeByCode($validated["uuid"], $validated["code"]);

    if ($result["status"] === "ok") {
      return $this->success([], "Email berhasil diubah dan terverifikasi.");
    }
    if ($result["status"] === "expired") {
      return $this->error("Kode verifikasi telah kedaluwarsa.", 422);
    }
    if ($result["status"] === "invalid") {
      return $this->error("Kode verifikasi salah.", 422);
    }
    if ($result["status"] === "email_taken") {
      return $this->error("Email sudah digunakan oleh akun lain.", 422);
    }
    if ($result["status"] === "not_found") {
      return $this->error("Tautan verifikasi tidak ditemukan.", 404);
    }

    return $this->error("Verifikasi perubahan email gagal.", 422);
  }

  /**
   * Verifikasi Email dengan OTP Code
   *
   *
   * @summary Verifikasi Email dengan OTP Code
   *
   * @description Verifikasi email menggunakan kode OTP (6 digit) yang dikirim ke email pengguna. Endpoint ini dapat menerima UUID atau token sebagai identifier, kemudian dikombinasikan dengan kode OTP untuk verifikasi.
   *
   * **Cara Kerja:**
   * 1. Setelah registrasi, pengguna akan menerima email berisi kode OTP 6 digit dan link verifikasi
   * 2. Pengguna dapat menggunakan endpoint ini dengan mengirim UUID/token dan kode OTP
   * 3. Jika kode valid dan belum expired, email akan terverifikasi dan status user menjadi aktif
   *
   * **Parameter:**
   * - `uuid` atau `token`: UUID atau token verifikasi (dari email atau response registrasi)
   * - `code`: Kode OTP 6 digit yang diterima via email
   *
   * @response 200 scenario="Success" {"success":true,"message":"Email Anda berhasil diverifikasi.","data":[]}
   * @response 404 scenario="Not Found" {"success":false,"message":"Tautan verifikasi tidak ditemukan."}
   * @response 422 scenario="Expired" {"success":false,"message":"Kode verifikasi telah kedaluwarsa."}
   * @response 422 scenario="Invalid" {"success":false,"message":"Kode verifikasi salah atau token tidak valid."}
   *
   * @unauthenticated
   */
  public function verifyEmail(VerifyEmailRequest $request): JsonResponse
  {
    $request->validated();

    $uuidOrToken = $request->input("token") ?? $request->input("uuid");
    $code = $request->string("code");

    $result = $this->emailVerification->verifyByCode($uuidOrToken, $code);

    if ($result["status"] === "ok") {
      return $this->success([], "Email Anda berhasil diverifikasi.");
    }

    if ($result["status"] === "expired") {
      return $this->error("Kode verifikasi telah kedaluwarsa.", 422);
    }

    if ($result["status"] === "invalid") {
      return $this->error("Kode verifikasi salah atau token tidak valid.", 422);
    }

    if ($result["status"] === "not_found") {
      return $this->error("Tautan verifikasi tidak ditemukan.", 404);
    }

    return $this->error("Verifikasi gagal.", 422);
  }

  /**
   * Verifikasi Email dengan Magic Link Token
   *
   *
   * @summary Verifikasi Email dengan Magic Link Token
   *
   * @description Verifikasi email menggunakan magic link token (16 karakter) yang dikirim melalui link di email. Endpoint ini digunakan untuk verifikasi otomatis ketika pengguna mengklik link verifikasi di email tanpa perlu memasukkan kode OTP.
   *
   * **Cara Kerja:**
   * 1. Setelah registrasi, pengguna akan menerima email berisi link verifikasi dengan token
   * 2. Ketika pengguna mengklik link, frontend akan otomatis memanggil endpoint ini dengan token dari URL
   * 3. Jika token valid dan belum expired, email akan terverifikasi dan status user menjadi aktif
   *
   * **Perbedaan dengan OTP:**
   * - Magic Link: Hanya perlu token (16 karakter), lebih mudah untuk user (one-click verification)
   * - OTP: Perlu UUID/token + kode 6 digit, lebih aman karena memerlukan akses ke email
   *
   * **Parameter:**
   * - `token`: Token verifikasi 16 karakter dari link email
   *
   * @response 200 scenario="Success" {"success":true,"message":"Email Anda berhasil diverifikasi.","data":[]}
   * @response 404 scenario="Not Found" {"success":false,"message":"Link verifikasi tidak ditemukan."}
   * @response 422 scenario="Expired" {"success":false,"message":"Link verifikasi telah kedaluwarsa."}
   * @response 422 scenario="Invalid" {"success":false,"message":"Link verifikasi tidak valid atau sudah digunakan."}
   *
   * @unauthenticated
   */
  public function verifyEmailByToken(VerifyEmailByTokenRequest $request): JsonResponse
  {
    $request->validated();
    $token = $request->string("token");

    $result = $this->emailVerification->verifyByToken($token);

    if ($result["status"] === "ok") {
      return $this->success([], "Email Anda berhasil diverifikasi.");
    }

    if ($result["status"] === "expired") {
      return $this->error("Link verifikasi telah kedaluwarsa.", 422);
    }

    if ($result["status"] === "invalid") {
      return $this->error("Link verifikasi tidak valid atau sudah digunakan.", 422);
    }

    if ($result["status"] === "not_found") {
      return $this->error("Link verifikasi tidak ditemukan.", 404);
    }

    return $this->error("Verifikasi gagal.", 422);
  }

  /**
   * Kirim Ulang Kredensial Akun
   *
   *
   * @summary Kirim Ulang Kredensial Akun
   *
   * @description Mengirim ulang kredensial (password baru) ke akun Admin/Instructor/Superadmin yang berstatus pending. **Memerlukan role: Superadmin**
   *
   * @response 200 scenario="Success" {"success": true, "message": "Kredensial berhasil dikirim ulang.", "data": {"user": {"id": 2, "name": "Jane Instructor", "email": "jane@example.com", "status": "pending"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk melakukan aksi ini."}
   * @response 404 scenario="Not Found" {"success":false,"message":"User tidak ditemukan"}
   * @response 422 scenario="Invalid User" {"success":false,"message":"Hanya untuk akun Admin, Superadmin, atau Instructor yang berstatus pending."}
   *
   * @authenticated
   */
  public function resendCredentials(ResendCredentialsRequest $request): JsonResponse
  {
    $validated = $request->validated();
    $target = User::query()->find($validated["user_id"]);
    if (!$target) {
      return $this->error("User tidak ditemukan", 404);
    }

    $isAllowedRole =
      $target->hasRole("Admin") || $target->hasRole("Superadmin") || $target->hasRole("Instructor");
    $isPending = ($target->status ?? null) === UserStatus::Pending;
    if (!($isAllowedRole && $isPending)) {
      return $this->error(
        "Hanya untuk akun Admin, Superadmin, atau Instructor yang berstatus pending.",
        422,
      );
    }

    $reflection = new \ReflectionClass($this->auth);
    $passwordPlain = $reflection
      ->getMethod("generatePasswordFromNameEmail")
      ->invoke($this->auth, $target->name, $target->email);
    $target->password = \Illuminate\Support\Facades\Hash::make($passwordPlain);
    $target->save();

    $reflection
      ->getMethod("sendGeneratedPasswordEmail")
      ->invoke($this->auth, $target, $passwordPlain);

    return $this->success(["user" => $target->toArray()], "Kredensial berhasil dikirim ulang.");
  }

  /**
   * Perbarui Status Pengguna
   *
   *
   * @summary Perbarui Status Pengguna
   *
   * @description Memperbarui status pengguna (pending, active, inactive, banned). **Memerlukan role: Superadmin**
   *
   * @response 200 scenario="Success" {"success": true, "message": "Status pengguna berhasil diperbarui.", "data": {"user": {"id": 1, "name": "John Doe", "email": "john@example.com", "status": "active"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk melakukan aksi ini."}
   * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"status": ["Status tidak valid."]}}
   *
   * @authenticated
   */
  public function updateUserStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
  {
    try {
      $updated = $this->auth->updateUserStatus($user, (string) $request->string("status"));
    } catch (ValidationException $e) {
      return $this->validationError($e->errors());
    }

    return $this->success(["user" => $updated->toArray()], "Status pengguna berhasil diperbarui.");
  }

  /**
   * Daftar Semua Pengguna
   *
   *
   * @summary Daftar Semua Pengguna
   *
   * @description Mengambil daftar semua pengguna dengan pagination dan filter. **Memerlukan role: Admin atau Superadmin**
   *
   * @queryParam search string Kata kunci pencarian (nama, email, username). Example: john
   * @queryParam page integer Nomor halaman. Example: 1
   * @queryParam per_page integer Jumlah item per halaman (default: 15). Example: 15
   * @queryParam filter[status] string Filter berdasarkan status (pending|active|inactive|banned). Example: active
   * @queryParam filter[role] string Filter berdasarkan role pengguna (Student|Instructor|Admin|Superadmin). Example: Student
   * @queryParam filter[created_from] string Filter berdasarkan tanggal dibuat (dari, format: Y-m-d). Example: 2025-01-01
   * @queryParam filter[created_to] string Filter berdasarkan tanggal dibuat (sampai, format: Y-m-d). Example: 2025-12-31
   * @queryParam sort string Field untuk sorting. Allowed: name, email, username, status, created_at. Prefix dengan '-' untuk descending. Example: -created_at
   *
   * @allowedFilters status, role, created_from, created_to
   *
   * @allowedSorts name, email, username, status, created_at
   *
   * @filterEnum status pending|active|inactive|banned
   * @filterEnum role Student|Instructor|Admin|Superadmin
   *
   * @response 200 scenario="Success" {"success": true, "message": "Success", "data": [{"id": 1, "name": "John Doe", "email": "john@example.com", "status": "active"}], "meta": {"current_page": 1, "last_page": 5, "per_page": 15, "total": 75}, "links": {"first": "...", "last": "...", "prev": null, "next": "..."}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function listUsers(Request $request): JsonResponse
  {
    /** @var \Modules\Auth\Models\User|null $authUser */
    $authUser = auth("api")->user();
    if (!$authUser) {
      return $this->error("Tidak terotorisasi.", 401);
    }

    $isSuperadmin = $authUser->hasRole("Superadmin");
    $isAdmin = $authUser->hasRole("Admin");
    if (!$isSuperadmin && !$isAdmin) {
      return $this->error("Tidak terotorisasi.", 403);
    }

    $perPage = max(1, (int) $request->query("per_page", 15));
    $paginator = $this->auth->listUsers($authUser, $perPage);

    return $this->paginateResponse($paginator);
  }

  /**
   * Detail Pengguna
   *
   *
   * @summary Detail Pengguna
   *
   * @description Mengambil detail pengguna berdasarkan ID. **Memerlukan role: Superadmin**
   *
   * @response 200 scenario="Success" {"success": true, "message": "Success", "data": {"user": {"id": 1, "name": "John Doe", "email": "john@example.com", "username": "johndoe", "status": "active", "roles": ["Student"]}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk melihat user ini."}
   *
   * @authenticated
   */
  public function showUser(User $user): JsonResponse
  {
    /** @var \Modules\Auth\Models\User|null $authUser */
    $authUser = auth("api")->user();
    if (!$authUser) {
      return $this->error("Tidak terotorisasi.", 401);
    }

    try {
      $data = $this->auth->showUser($authUser, $user);
    } catch (AuthorizationException $e) {
      return $this->error($e->getMessage(), 403);
    }

    return $this->success(["user" => $data]);
  }

  /**
   * Atur Username Pertama Kali
   *
   *
   * @summary Atur Username Pertama Kali
   *
   * @description Mengatur username untuk pertama kali (biasanya setelah login via Google OAuth).
   *
   * @response 200 scenario="Success" {"success": true, "message": "Username berhasil diatur.", "data": {"user": {"id": 1, "name": "John Doe", "email": "john@example.com", "username": "johndoe"}}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 422 scenario="Already Set" {"success":false,"message":"Username sudah diatur untuk akun Anda."}
   * @response 422 scenario="Validation Error" {"success": false, "message": "Validasi gagal.", "errors": {"username": ["Username sudah digunakan."]}}
   *
   * @authenticated
   */
  public function setUsername(SetUsernameRequest $request): JsonResponse
  {
    $user = auth("api")->user();
    if (!$user) {
      return $this->error("Tidak terotorisasi.", 401);
    }

    if ($user->username) {
      return $this->error("Username sudah diatur untuk akun Anda.", 422);
    }

    $data = $this->auth->setUsername($user, $request->validated("username"));

    return $this->success($data, "Username berhasil diatur.");
  }
}
