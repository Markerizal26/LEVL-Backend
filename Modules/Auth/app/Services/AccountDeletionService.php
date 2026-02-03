<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Mail\Mail\Auth\AccountDeletionVerificationMail;
use Modules\Auth\Models\OtpCode;
use Modules\Auth\Models\User;
use Modules\Common\Models\SystemSetting;

class AccountDeletionService
{
    public const PURPOSE = 'account_deletion';

    public function requestDeletion(User $user, string $password): string
    {
        if ($user->account_status === 'deleted') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'account' => [__('messages.account.deletion_in_progress')],
            ]);
        }

        if (! Hash::check($password, $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'password' => [__('messages.auth.password_incorrect')],
            ]);
        }

        
        OtpCode::query()
            ->forUser($user)
            ->forPurpose(self::PURPOSE)
            ->valid()
            ->update(['consumed_at' => now()]);

        $ttlMinutes = (int) (SystemSetting::get('auth_account_deletion_ttl_minutes', 60) ?? 60);

        $uuid = (string) Str::uuid();
        $token = Str::random(16);
        $tokenHash = hash('sha256', $token);

        $otp = OtpCode::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'channel' => 'email',
            'provider' => 'mailhog',
            'purpose' => self::PURPOSE,
            'code' => 'magic',
            'meta' => ['token_hash' => $tokenHash],
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        $frontendUrl = config('app.frontend_url');
        $verifyUrl = $frontendUrl.'/profile/account/delete/confirm?token='.$token.'&uuid='.$uuid;

        Mail::to($user->email)->send(new \Modules\Mail\Mail\Auth\AccountDeletionVerificationMail($user->email, $verifyUrl, $ttlMinutes));

        return $uuid;
    }

    public function confirmDeletion(string $token, string $uuid): bool
    {
        $tokenHash = hash('sha256', $token);

        $otp = OtpCode::query()
            ->forPurpose(self::PURPOSE)
            ->where('uuid', $uuid)
            ->valid()
            ->first();

        if (! $otp || ! isset($otp->meta['token_hash']) || ! hash_equals($otp->meta['token_hash'], $tokenHash)) {
            return false;
        }

        if ($otp->isConsumed()) {
            return false;
        }

        $user = User::query()->find($otp->user_id);
        if (! $user) {
            return false;
        }

        $otp->markAsConsumed();
        $user->account_status = 'deleted';
        $user->save();

        
        
        $user->delete();

        return true;
    }
}
