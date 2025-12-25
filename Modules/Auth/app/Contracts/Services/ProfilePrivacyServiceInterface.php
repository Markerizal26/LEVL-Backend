<?php

namespace Modules\Auth\Contracts\Services;

use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;

interface ProfilePrivacyServiceInterface
{
    public function updatePrivacySettings(User $user, array $settings): ProfilePrivacySetting;

    public function getPrivacySettings(User $user): ProfilePrivacySetting;

    public function createDefaultSettings(User $user): ProfilePrivacySetting;

    public function canViewProfile(User $user, User $viewer): bool;

    public function canViewField(User $user, string $field, User $viewer): bool;

    public function filterProfileData(array $data, User $user, User $viewer): array;

    public function isFieldVisible(User $user, string $field, User $viewer): bool;

    public function getVisibleFields(User $user, User $viewer): array;
}
