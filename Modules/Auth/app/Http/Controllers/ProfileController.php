<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Contracts\Services\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Requests\UpdateProfileRequest;

class ProfileController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ProfileServiceInterface $profileService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->relationLoaded('media')) {
            $user->load('media');
        }
    $user->loadMissing('roles');
        $profileData = $this->profileService->getProfileData($user);

        return $this->success(new \Modules\Auth\Http\Resources\ProfileResource($profileData));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $updatedUser = $this->profileService->updateProfile($user, $request->validated());
    $updatedUser->loadMissing('roles');

        return $this->success(
            $this->profileService->getProfileData($updatedUser),
            'Profile updated successfully.'
        );
    }

    public function uploadAvatar(\Modules\Auth\Http\Requests\UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->relationLoaded('media')) {
            $user->load('media');
        }
        $avatarUrl = $this->profileService->uploadAvatar($user, $request->file('avatar'));

        return $this->success(['avatar_url' => $avatarUrl], 'Avatar uploaded successfully.');
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->profileService->deleteAvatar($user);

        return $this->success(null, __('messages.auth.avatar_deleted'));
    }

    public function requestEmailChange(\Modules\Auth\Http\Requests\RequestEmailChangeRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        
        $uuid = $this->profileService->requestEmailChange(
            $user, 
            $request->input('new_email'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(['uuid' => $uuid], __('messages.auth.email_change_request_sent'));
    }

    public function verifyEmailChange(\Modules\Auth\Http\Requests\VerifyEmailChangeRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $result = $this->profileService->verifyEmailChange(
            $user,
            $request->input('token'),
            $request->input('uuid')
        );

        if ($result['status'] !== 'ok') {
            return $this->error(__('messages.auth.email_change_' . $result['status']), [], 422);
        }

        return $this->success([], __('messages.auth.email_change_success'));
    }
}
