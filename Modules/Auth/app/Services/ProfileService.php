<?php

namespace Modules\Auth\Services;

use App\Contracts\Services\ProfileServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Events\AccountDeleted;
use Modules\Auth\Events\PasswordChanged;
use Modules\Auth\Events\ProfileUpdated;
use Modules\Auth\Models\User;

class ProfileService implements ProfileServiceInterface
{
    public function __construct(
        private ProfileStatisticsService $statisticsService,
        private ProfilePrivacyService $privacyService,
        private UserActivityService $activityService
    ) {}

    public function updateProfile(User $user, array $data): User
    {
        $validator = Validator::make($data, [
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'phone' => 'sometimes|nullable|string|max:20',
            'bio' => 'sometimes|nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $oldEmail = $user->email;

        $user->fill($data);
        $user->last_profile_update = now();
        $user->save();

        if (isset($data['email']) && $data['email'] !== $oldEmail) {
            $user->email_verified_at = null;
            $user->save();
        }

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

        return $media->getUrl();
    }

    public function deleteAvatar(User $user): void
    {
        $user->clearMediaCollection('avatar');
        $user->last_profile_update = now();
        $user->save();
    }

    public function getProfileData(User $user, ?User $viewer = null): array
    {
        $viewer = $viewer ?? $user;

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'bio' => $user->bio,
            'avatar_url' => $user->avatar_url,
            'account_status' => $user->account_status,
            'last_profile_update' => $user->last_profile_update,
            'created_at' => $user->created_at,
        ];

        if ($viewer->id !== $user->id) {
            $data = $this->privacyService->filterProfileData($data, $user, $viewer);
        }

        return $data;
    }

    public function getPublicProfile(User $user, User $viewer): array
    {
        if (! $this->privacyService->canViewProfile($user, $viewer)) {
            throw new \Exception(__('messages.profile.no_permission'));
        }

        $profileData = $this->getProfileData($user, $viewer);

        $visibleFields = collect($user->getVisibleFieldsFor($viewer));

        if ($visibleFields->contains('*') || $visibleFields->contains('statistics')) {
            $profileData['statistics'] = $this->statisticsService->getStatistics($user);
        }

        if ($visibleFields->contains('*') || $visibleFields->contains('achievements')) {
            $profileData['achievements'] = [
                'badges' => $user->badges()->with('badge')->get(),
                'pinned_badges' => $user->pinnedBadges()->with('badge')->orderBy('order')->get(),
            ];
        }

        if ($visibleFields->contains('*') || $visibleFields->contains('activity_history')) {
            $profileData['recent_activities'] = $this->activityService->getRecentActivities($user, 10);
        }

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

        event(new PasswordChanged($user));

        return true;
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
        $user->restore();
        $user->account_status = 'active';
        $user->save();

        return true;
    }
}
