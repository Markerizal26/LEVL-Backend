<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Support;

use App\Jobs\CreateAuditJob;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Contracts\Repositories\AuthRepositoryInterface;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Modules\Auth\Services\UserCacheService;
use Modules\Mail\Mail\Auth\UserCredentialsMail;

class UserLifecycleProcessor
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly UserCacheService $cacheService,
        private readonly UserFinder $finder
    ) {}

    public function updateUserStatus(User $authUser, int $userId, string $status): User
    {
        $user = $this->finder->showUser($authUser, $userId);

        if ($status === UserStatus::Pending->value) {
            throw ValidationException::withMessages([
                'status' => [__('messages.auth.status_cannot_be_pending')],
            ]);
        }

        if ($user->status === UserStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => [__('messages.auth.status_cannot_be_changed_from_pending')],
            ]);
        }

        return DB::transaction(function () use ($user, $status) {
            $user->status = UserStatus::from($status);
            $user->save();

            $this->cacheService->invalidateUser($user->id);

            return $user->fresh();
        });
    }

    public function deleteUser(User $authUser, int $userId): void
    {
        $user = $this->finder->showUser($authUser, $userId);

        if ($user->id === $authUser->id) {
            throw ValidationException::withMessages([
                'account' => [__('messages.auth.cannot_delete_self')],
            ]);
        }

        if (! $authUser->hasRole('Superadmin')) {
            if ($user->hasRole('Superadmin')) {
                throw new AuthorizationException(__('messages.forbidden'));
            }
        }

        $user->delete();
    }

    public function createUser(User $authUser, array $validated): User
    {
        $role = $validated['role'];

        if ($role === config('auth.default_role', 'Student')) {
            throw ValidationException::withMessages([
                'role' => [__('messages.auth.student_creation_forbidden')],
            ]);
        }

        if ($authUser->hasRole('Admin') && ! $authUser->hasRole('Superadmin')) {
            if (! in_array($role, ['Admin', 'Instructor'])) {
                throw new AuthorizationException(__('messages.forbidden'));
            }
        } elseif ($authUser->hasRole('Superadmin')) {
            if (! in_array($role, ['Superadmin', 'Admin', 'Instructor'])) {
                throw new AuthorizationException(__('messages.forbidden'));
            }
        } else {
            throw new AuthorizationException(__('messages.unauthorized'));
        }

        $passwordPlain = Str::random(12);
        unset($validated['role']);
        $validated['password'] = Hash::make($passwordPlain);

        $user = $this->authRepository->createUser($validated + ['is_password_set' => false]);
        $user->assignRole($role);

        $this->sendCredentialsEmail($user, $passwordPlain);

        return $user;
    }

    public function updateProfile(User $user, array $validated, ?string $ip, ?string $userAgent): User
    {
        return DB::transaction(function () use ($user, $validated, $ip, $userAgent) {
            $changes = [];
            foreach (['name', 'username'] as $field) {
                if (isset($validated[$field]) && $user->{$field} !== $validated[$field]) {
                    $changes[$field] = [$user->{$field}, $validated[$field]];
                    $user->{$field} = $validated[$field];
                }
            }

            $user->save();

            if (! empty($changes)) {
                dispatch(new CreateAuditJob([
                    'action' => 'update',
                    'user_id' => $user->id,
                    'module' => 'Auth',
                    'target_table' => 'users',
                    'target_id' => $user->id,
                    'meta' => ['action' => 'profile.update', 'changes' => $changes],
                    'logged_at' => now(),
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                ]));
            }

            return $user->fresh();
        });
    }

    protected function sendCredentialsEmail(User $user, string $passwordPlain): void
    {
        $loginUrl = rtrim(config('app.frontend_url', 'http://localhost:3000'), '/').'/login';

        Mail::to($user->email)->send(new UserCredentialsMail($user, $passwordPlain, $loginUrl));
    }
}
