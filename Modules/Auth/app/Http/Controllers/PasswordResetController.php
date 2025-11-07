<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Http\Requests\ChangePasswordRequest;
use Modules\Auth\Http\Requests\ForgotPasswordRequest;
use Modules\Auth\Http\Requests\ResetPasswordRequest;
use Modules\Auth\Mail\ResetPasswordMail;
use Modules\Auth\Models\PasswordResetToken;
use Modules\Auth\Models\User;

class PasswordResetController extends Controller
{
    use ApiResponse;

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var User|null $user */
        $user = User::query()
            ->where(fn ($q) => $q->where('email', $validated['login'])->orWhere('username', $validated['login']))
            ->first();
        if (! $user) {
            return $this->success([], 'Jika email atau username terdaftar, kami telah mengirimkan instruksi reset kata sandi.');
        }

        PasswordResetToken::query()->where('email', $user->email)->delete();

        // 6-digit numeric code
        $plainToken = (string) random_int(100000, 999999);
        $hashed = Hash::make($plainToken);

        PasswordResetToken::create([
            'email' => $user->email,
            'token' => $hashed,
            'created_at' => now(),
        ]);

        $ttlMinutes = (int) (config('auth.passwords.users.expire', 60) ?? 60);
        $frontendUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/');
        $resetUrl = $frontendUrl.'/reset-password?token='.$plainToken;

        Mail::to($user)->send(new ResetPasswordMail($user, $resetUrl, $ttlMinutes, $plainToken));

        return $this->success([], 'Jika email atau username terdaftar, kami telah mengirimkan instruksi reset kata sandi.');
    }

    public function confirmForgot(ResetPasswordRequest $request): JsonResponse
    {
        $token = $request->string('token');
        $password = $request->string('password');

        $ttlMinutes = (int) (config('auth.passwords.users.expire', 60) ?? 60);
        $candidateRecords = PasswordResetToken::query()
            ->where('created_at', '>=', now()->subMinutes($ttlMinutes + 5))
            ->latest('created_at')
            ->limit(100)
            ->get();

        $matched = null;
        foreach ($candidateRecords as $rec) {
            if (Hash::check($token, $rec->token)) {
                $matched = $rec;
                break;
            }
        }

        if (! $matched) {
            return $this->error('Token reset tidak valid atau telah kedaluwarsa.', 422);
        }

        /** @var User|null $user */
        $user = User::query()->where('email', $matched->email)->first();
        if (! $user) {
            PasswordResetToken::query()->where('email', $matched->email)->delete();

            return $this->error('Pengguna tidak ditemukan.', 404);
        }

        if (now()->diffInMinutes($matched->created_at) > $ttlMinutes) {
            PasswordResetToken::query()->where('email', $matched->email)->delete();

            return $this->error('Token reset telah kedaluwarsa.', 422);
        }

        $user->forceFill([
            'password' => Hash::make($password),
        ])->save();

        PasswordResetToken::query()->where('email', $matched->email)->delete();

        return $this->success([], 'Kata sandi berhasil direset.');
    }

    public function reset(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        if (! Hash::check($request->string('current_password'), $user->password)) {
            return $this->error('Password lama tidak cocok.', 422);
        }

        $user->forceFill([
            'password' => Hash::make($request->string('password')),
        ])->save();

        return $this->success([], 'Kata sandi berhasil diperbarui.');
    }
}
