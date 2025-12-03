<?php

namespace Modules\Auth\Http\Controllers;

use App\Contracts\Services\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\ProfileAuditLog;
use Modules\Auth\Models\User;

class AdminProfileController extends Controller
{
    public function __construct(
        private ProfileServiceInterface $profileService
    ) {
        $this->middleware('role:Admin');
    }

    public function show(Request $request, int $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            $profileData = $this->profileService->getProfileData($user, $request->user());

            return response()->json([
                'success' => true,
                'data' => $profileData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => "sometimes|email|unique:users,email,{$userId}",
            'phone' => 'sometimes|nullable|string|max:20',
            'bio' => 'sometimes|nullable|string|max:1000',
            'account_status' => 'sometimes|in:active,suspended,deleted',
        ]);

        try {
            $user = User::findOrFail($userId);
            $admin = $request->user();

            $oldData = $user->only(['name', 'email', 'phone', 'bio', 'account_status']);

            $updatedUser = $this->profileService->updateProfile($user, $request->all());

            // Log admin action
            ProfileAuditLog::create([
                'user_id' => $user->id,
                'admin_id' => $admin->id,
                'action' => 'profile_updated',
                'changes' => [
                    'old' => $oldData,
                    'new' => $updatedUser->only(['name', 'email', 'phone', 'bio', 'account_status']),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User profile updated successfully.',
                'data' => $this->profileService->getProfileData($updatedUser, $admin),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function suspend(Request $request, int $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            $admin = $request->user();

            $user->account_status = 'suspended';
            $user->save();

            // Log admin action
            ProfileAuditLog::create([
                'user_id' => $user->id,
                'admin_id' => $admin->id,
                'action' => 'account_suspended',
                'changes' => ['status' => 'suspended'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User account suspended successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function activate(Request $request, int $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            $admin = $request->user();

            $user->account_status = 'active';
            $user->save();

            // Log admin action
            ProfileAuditLog::create([
                'user_id' => $user->id,
                'admin_id' => $admin->id,
                'action' => 'account_activated',
                'changes' => ['status' => 'active'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User account activated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function auditLogs(Request $request, int $userId): JsonResponse
    {
        try {
            $logs = ProfileAuditLog::where('user_id', $userId)
                ->with('admin:id,name,email')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
