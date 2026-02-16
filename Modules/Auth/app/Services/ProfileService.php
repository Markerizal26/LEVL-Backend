<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use App\Contracts\Services\ProfileServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Events\AccountDeleted;
use Modules\Auth\Events\PasswordChanged;
use Modules\Auth\Events\ProfileUpdated;
use Modules\Auth\Contracts\Services\AuthServiceInterface;
use Modules\Auth\Contracts\Services\EmailVerificationServiceInterface;
use Modules\Auth\Models\User;

class ProfileService implements ProfileServiceInterface
{
    public function __construct(
        private ProfilePrivacyService $privacyService,
        private UserActivityService $activityService,
        private EmailVerificationServiceInterface $emailVerification,
        private AuthServiceInterface $authService
    ) {}

    public function updateProfile(User $user, array $data): User
    {
        $oldEmail = $user->email;

        $user->fill($data);
        $user->last_profile_update = now();
        $user->save();

        if (isset($data['email']) && $data['email'] !== $oldEmail) {
            $user->email_verified_at = null;
            $user->save();
        }

        \Illuminate\Support\Facades\Cache::tags(['user_profile'])->forget('user_profile:'.$user->id);

        event(new ProfileUpdated($user, $oldEmail !== $user->email));

        return $user->fresh();
    }

    public function uploadAvatar(User $user, UploadedFile $file): string
    {
        $user->clearMediaCollection('avatar');

        $media = $user
            ->addMedia($file)
            ->toMediaCollection('avatar');

        $user->last_profile_update = now();
        $user->save();

        \Illuminate\Support\Facades\Cache::tags(['user_profile'])->forget('user_profile:'.$user->id);

        return $media->getUrl();
    }

    public function deleteAvatar(User $user): void
    {
        $user->clearMediaCollection('avatar');
        $user->last_profile_update = now();
        $user->save();

        \Illuminate\Support\Facades\Cache::tags(['user_profile'])->forget('user_profile:'.$user->id);
    }

    public function getProfileData(User $user, ?User $viewer = null): array
    {
        $viewer = $viewer ?? $user;

        $roleNames = $user->getRoleNames()->values();
        $primaryRole = Str::lower((string) ($roleNames->first() ?? 'student'));

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'bio' => $user->bio,
            'avatar_url' => $user->avatar_url,
            'account_status' => $user->account_status,
            'status' => $user->account_status,
            'role' => $primaryRole,
            'roles' => $roleNames->all(),
            'email_verified_at' => $user->email_verified_at,
            'last_profile_update' => $user->last_profile_update,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        if ($viewer->id !== $user->id) {
            $data = $this->privacyService->filterProfileData($data, $user, $viewer);
        }

        return $data;
    }

    public function getPublicProfile(User $user, User $viewer): array
    {
        if (! $this->privacyService->canViewProfile($user, $viewer)) {
            throw new \Illuminate\Auth\Access\AuthorizationException(__('messages.profile.no_permission'));
        }

        $profileData = $this->getProfileData($user, $viewer);

        $visibleFields = collect($user->getVisibleFieldsFor($viewer));

        return $profileData;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new \Exception(__('messages.auth.current_password_incorrect'));
        }

        if (strlen($newPassword) < 8) {
            throw new \Exception(__('messages.auth.password_min_length'));
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        \Illuminate\Support\Facades\Cache::tags(['user_profile'])->forget('user_profile:'.$user->id);

        event(new PasswordChanged($user));

        return true;
    }

    public function requestEmailChange(User $user, string $newEmail, ?string $ip, ?string $userAgent): ?string
    {
        $uuid = $this->emailVerification->sendChangeEmailLink($user, $newEmail);
        
        
        $this->authService->logEmailChangeRequest($user, $newEmail, $uuid, $ip, $userAgent);

        return $uuid;
    }

    public function verifyEmailChange(User $user, string $token, string $uuid): array
    {
        return $this->emailVerification->verifyChangeByToken($token, $uuid);
    }

    public function deleteAccount(User $user, string $password): bool
    {
        if (! Hash::check($password, $user->password)) {
            throw new \Exception(__('messages.auth.password_incorrect'));
        }

        $user->account_status = 'deleted';
        $user->save();
        $user->delete();

        event(new AccountDeleted($user));

        return true;
    }

    public function restoreAccount(User $user): bool
    {
        if ($user->account_status !== 'deleted' || !$user->trashed()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'account' => [__('messages.account.restore_not_deleted')],
            ]);
        }

        $days = (int) ((\Modules\Common\Models\SystemSetting::get('auth_account_retention_days', 30)) ?? 30);
        
        if ($user->deleted_at->addDays($days)->isPast()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'account' => [__('messages.account.restore_expired', ['days' => $days])],
            ]);
        }

        $user->restore();
        $user->account_status = 'active';
        $user->save();

        return true;
    }
}
