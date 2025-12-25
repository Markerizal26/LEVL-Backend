<?php

namespace Modules\Auth\Services;

use Modules\Auth\Contracts\Services\ProfilePrivacyServiceInterface;
use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;

class ProfilePrivacyService implements ProfilePrivacyServiceInterface
{
    public function updatePrivacySettings(User $user, array $settings): ProfilePrivacySetting
    {
        return $user->privacySettings()->updateOrCreate(
            ['user_id' => $user->id],
            $settings
        );
    }

    public function getPrivacySettings(User $user): ProfilePrivacySetting
    {
        return $user->privacySettings ?? $this->createDefaultSettings($user);
    }

    public function createDefaultSettings(User $user): ProfilePrivacySetting
    {
        return ProfilePrivacySetting::create([
            'user_id' => $user->id,
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PUBLIC,
            'show_email' => false,
            'show_phone' => false,
            'show_activity_history' => true,
            'show_achievements' => true,
            'show_statistics' => true,
        ]);
    }

    public function canViewProfile(User $user, User $viewer): bool
    {
        return $user->canBeViewedBy($viewer);
    }

    public function canViewField(User $user, string $field, User $viewer): bool
    {
        $privacySettings = $this->getPrivacySettings($user);

        return $privacySettings->canShowField($field, $viewer);
    }

    public function filterProfileData(array $data, User $user, User $viewer): array
    {
        $visibleFields = collect($user->getVisibleFieldsFor($viewer));

        if ($visibleFields->contains('*')) {
            return $data;
        }

        return collect($data)
            ->filter(fn($value, $key) => $visibleFields->contains($key))
            ->toArray();
    }

    public function isFieldVisible(User $user, string $field, User $viewer): bool
    {
        return $this->canViewField($user, $field, $viewer);
    }

    public function getVisibleFields(User $user, User $viewer): array
    {
        return $user->getVisibleFieldsFor($viewer);
    }
}
