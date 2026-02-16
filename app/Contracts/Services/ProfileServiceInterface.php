<?php

namespace App\Contracts\Services;

use Illuminate\Http\UploadedFile;
use Modules\Auth\Models\User;

interface ProfileServiceInterface
{
    /**
     * Update user profile.
     *
     * @param  User  $user  The user whose profile to update
     * @param  array  $data  Profile data including name, email, phone, bio
     * @return User The updated user
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails
     */
    public function updateProfile(User $user, array $data): User;

    /**
     * Upload user avatar.
     *
     * @param  User  $user  The user uploading the avatar
     * @param  UploadedFile  $file  The avatar file
     * @return string The public URL of the uploaded avatar
     */
    public function uploadAvatar(User $user, UploadedFile $file): string;

    /**
     * Delete user avatar.
     *
     * @param  User  $user  The user whose avatar to delete
     */
    public function deleteAvatar(User $user): void;

    /**
     * Get profile data for a user.
     *
     * @param  User  $user  The user whose profile to retrieve
     * @param  User|null  $viewer  The user viewing the profile (null for self)
     * @return array Profile data array
     */
    public function getProfileData(User $user, ?User $viewer = null): array;

    /**
     * Get public profile for a user.
     *
     * @param  User  $user  The user whose profile to retrieve
     * @param  User  $viewer  The user viewing the profile
     * @return array Public profile data array
     *
     * @throws \Exception If viewer doesn't have permission to view profile
     */
    public function getPublicProfile(User $user, User $viewer): array;

    /**
     * Change user password.
     *
     * @param  User  $user  The user changing their password
     * @param  string  $currentPassword  The current password
     * @param  string  $newPassword  The new password
     * @return bool True if password change was successful
     *
     * @throws \Exception If current password is incorrect or new password is invalid
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool;

    /**
     * Delete user account (soft delete).
     *
     * @param  User  $user  The user whose account to delete
     * @param  string  $password  The user's password for confirmation
     * @return bool True if account deletion was successful
     *
     * @throws \Exception If password is incorrect
     */
    public function deleteAccount(User $user, string $password): bool;

    /**
     * Restore a deleted user account.
     *
     * @param  User  $user  The user whose account to restore
     * @return bool True if account restoration was successful
     */
    public function restoreAccount(User $user): bool;

    /**
     * Request email change verification.
     *
     * @param  User  $user  The user requesting email change
     * @param  string  $newEmail  The new email address
     * @param  string|null  $ip  Request IP (for audit)
     * @param  string|null  $userAgent  Request user agent (for audit)
     * @return string|null UUID for verification process
     */
    public function requestEmailChange(User $user, string $newEmail, ?string $ip, ?string $userAgent): ?string;

    /**
     * Verify email change using token.
     *
     * @param  User  $user  The user verifying email change
     * @param  string  $token  Verification token
     * @param  string  $uuid  Verification UUID
     * @return array Status result payload
     */
    public function verifyEmailChange(User $user, string $token, string $uuid): array;
}
