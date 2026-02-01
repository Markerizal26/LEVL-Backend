<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Support;

use Illuminate\Support\Str;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\OtpCode;
use Modules\Auth\Models\User;

class VerificationValidator
{
    public const PURPOSE_REGISTER = 'register_verification';
    public const PURPOSE_CHANGE_EMAIL = 'email_change_verification';

    public function verifyByCode(string $uuidOrToken, string $code): array
    {
        $isUuid = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuidOrToken);

        if ($isUuid) {
            $otp = OtpCode::query()
                ->forPurpose(self::PURPOSE_REGISTER)
                ->where('uuid', $uuidOrToken)
                ->latest('id')
                ->first();
        } else {
            if (strlen($uuidOrToken) !== 16) {
                return ['status' => 'invalid'];
            }

            $tokenHash = hash('sha256', $uuidOrToken);

            $otp = OtpCode::query()
                ->forPurpose(self::PURPOSE_REGISTER)
                ->valid()
                ->get()
                ->first(function ($record) use ($tokenHash) {
                    return isset($record->meta['token_hash']) &&
                           hash_equals($record->meta['token_hash'], $tokenHash);
                });
        }

        if (! $otp) {
            return ['status' => 'not_found'];
        }

        if ($otp->isConsumed()) {
            return ['status' => 'invalid'];
        }

        if ($otp->isExpired()) {
            return ['status' => 'expired'];
        }

        if (! hash_equals($otp->code, $code)) {
            return ['status' => 'invalid'];
        }

        $user = User::query()->find($otp->user_id);
        if (! $user) {
            return ['status' => 'not_found'];
        }

        $otp->markAsConsumed();

        if (! $user->email_verified_at || $user->status !== UserStatus::Active) {
            $user->forceFill([
                'email_verified_at' => now(),
                'status' => UserStatus::Active,
            ])->save();
        }

        return ['status' => 'ok'];
    }

    public function verifyByToken(string $token, string $uuid): array
    {
        if (strlen($token) !== 16) {
            return ['status' => 'invalid'];
        }

        $tokenHash = hash('sha256', $token);

        $otp = OtpCode::query()
            ->forPurpose(self::PURPOSE_REGISTER)
            ->where('uuid', $uuid)
            ->valid()
            ->first();

        if (! $otp || ! isset($otp->meta['token_hash']) || ! hash_equals($otp->meta['token_hash'], $tokenHash)) {
            return ['status' => 'not_found'];
        }

        if ($otp->isConsumed()) {
            return ['status' => 'invalid'];
        }

        if ($otp->isExpired()) {
            return ['status' => 'expired'];
        }

        $user = User::query()->find($otp->user_id);
        if (! $user) {
            return ['status' => 'not_found'];
        }

        $otp->markAsConsumed();

        if (! $user->email_verified_at || $user->status !== UserStatus::Active) {
            $user->forceFill([
                'email_verified_at' => now(),
                'status' => UserStatus::Active,
            ])->save();
        }

        return ['status' => 'ok', 'user_id' => $user->id];
    }

    public function verifyChangeByToken(string $token, string $uuid): array
    {
        if (strlen($token) !== 16) {
            return ['status' => 'invalid'];
        }

        $tokenHash = hash('sha256', $token);

        $otp = OtpCode::query()
            ->forPurpose(self::PURPOSE_CHANGE_EMAIL)
            ->where('uuid', $uuid)
            ->valid()
            ->first();

        if (! $otp || ! isset($otp->meta['token_hash']) || ! hash_equals($otp->meta['token_hash'], $tokenHash)) {
            return ['status' => 'not_found'];
        }

        if ($otp->isConsumed()) {
            return ['status' => 'invalid'];
        }

        if ($otp->isExpired()) {
            return ['status' => 'expired'];
        }

        $user = User::query()->find($otp->user_id);
        if (! $user) {
            return ['status' => 'not_found'];
        }

        $newEmail = $otp->meta['new_email'] ?? null;
        if (! $newEmail) {
            return ['status' => 'invalid'];
        }

        if (User::query()->where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
            return ['status' => 'email_taken'];
        }

        $otp->markAsConsumed();

        $user->forceFill([
            'email' => $newEmail,
            'email_verified_at' => now(),
        ])->save();

        return ['status' => 'ok'];
    }
}
