<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Contracts\Repositories\AuthRepositoryInterface;
use Modules\Auth\Contracts\UserAccessPolicyInterface;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Modules\Auth\Contracts\Services\UserManagementServiceInterface;
use Modules\Common\Models\Audit;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\CourseAdmin;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class UserManagementService implements UserManagementServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly UserAccessPolicyInterface $userAccessPolicy,
    ) {}

    public function listUsers(User $authUser, int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        // Authorization check via policy
        if (!$authUser->can('viewAny', User::class)) {
            throw new AuthorizationException(__('messages.unauthorized'));
        }

        $query = QueryBuilder::for(User::class)
            ->select(['id', 'name', 'email', 'username', 'status', 'account_status', 'created_at', 'email_verified_at', 'is_password_set'])
            ->with(['roles', 'media']);

        if ($search && trim($search) !== '') {
            $ids = User::search($search)->keys()->toArray();
            $query->whereIn('id', $ids);
        }

        // Filter based on user role - Admin can only see users they manage
        if ($authUser->hasRole('Admin') && !$authUser->hasRole('Superadmin')) {
            $managedCourseIds = CourseAdmin::query()
                ->where('user_id', $authUser->id)
                ->pluck('course_id')
                ->unique();

            $query->where(function (Builder $q) use ($managedCourseIds) {
                // See all Admins (except Superadmin)
                $q->whereHas('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'Admin');
                })
                // OR see Instructors/Students in managed courses
                ->orWhere(function ($subQuery) use ($managedCourseIds) {
                    $subQuery->whereHas('roles', function ($roleQuery) {
                        $roleQuery->whereIn('name', ['Instructor', 'Student']);
                    })
                    ->whereHas('enrollments', function ($enrollmentQuery) use ($managedCourseIds) {
                        $enrollmentQuery->whereIn('course_id', $managedCourseIds);
                    });
                });
            });
        }

        return $query->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::callback('role', function (Builder $query, $value) {
                    $roles = is_array($value)
                      ? $value
                      : Str::of($value)->explode(',')->map(fn ($r) => trim($r))->toArray();
                    $query->whereHas('roles', fn ($q) => $q->whereIn('name', $roles));
                }),
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    if (is_string($value) && trim($value) !== '') {
                        $ids = User::search($value)->keys()->toArray();
                        $query->whereIn($query->getModel()->getTable().'.id', $ids);
                    }
                }),
            ])
            ->allowedSorts(['name', 'email', 'username', 'status', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($perPage);
    }

    public function showUser(User $authUser, int $userId): User
    {
        $target = User::findOrFail($userId);
        
        // Authorization check via policy
        if (!$authUser->can('view', $target)) {
            throw new AuthorizationException(__('messages.auth.no_access_to_user'));
        }

        return $target;
    }

    public function updateUserStatus(User $authUser, int $userId, string $status): User
    {
        $user = $this->showUser($authUser, $userId);

        if ($status === UserStatus::Pending->value) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => [__('messages.auth.status_cannot_be_pending')],
            ]);
        }

        if ($user->status === UserStatus::Pending) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => [__('messages.auth.status_cannot_be_changed_from_pending')],
            ]);
        }

        return DB::transaction(function () use ($user, $status) {
            $user->status = UserStatus::from($status);
            $user->save();
            return $user->fresh();
        });
    }

    public function deleteUser(User $authUser, int $userId): void
    {
        $user = $this->showUser($authUser, $userId);

        if ($user->id === $authUser->id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'account' => [__('messages.auth.cannot_delete_self')],
            ]);
        }

        if (!$authUser->hasRole('Superadmin')) {
            // Admin can only delete fellow Admins or managed Instructors/Students
            // (showUser already handles the scope check)
            if ($user->hasRole('Superadmin')) {
                throw new AuthorizationException(__('messages.forbidden'));
            }
        }

        $user->delete();
    }

    public function createUser(User $authUser, array $validated): User
    {
        $role = $validated['role'];

        // 1. Student can ONLY be created via registration, NOT via Admin API
        if ($role === 'Student') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'role' => [__('messages.auth.student_creation_forbidden')],
            ]);
        }

        // 2. Role-based restrictions
        if ($authUser->hasRole('Admin') && !$authUser->hasRole('Superadmin')) {
            // Admin can ONLY create Admin or Instructor
            if (!in_array($role, ['Admin', 'Instructor'])) {
                throw new AuthorizationException(__('messages.forbidden'));
            }
        } elseif ($authUser->hasRole('Superadmin')) {
            // Superadmin can create Superadmin, Admin, Instructor
            if (!in_array($role, ['Superadmin', 'Admin', 'Instructor'])) {
                throw new AuthorizationException(__('messages.forbidden'));
            }
        } else {
            throw new AuthorizationException(__('messages.unauthorized'));
        }

        $passwordPlain = Str::random(12);
        unset($validated['role']);
        $validated['password'] = \Illuminate\Support\Facades\Hash::make($passwordPlain);
        
        $user = $this->authRepository->createUser($validated + ['is_password_set' => false]);
        $user->assignRole($role);

        // TODO: Send credentials email...
        
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

            // Avatar handling should ideally be in a separate MediaService or handled here via Spatie Media Library
            // Keeping it here is fine as it's part of Profile Update use case.
            // But Controller logic had file handling. Service should receive `UploadedFile` or strict path?
            // Usually Controller handles upload, passes file.
            // Here assuming Controller handled it or we pass the array.
            
            $user->save();
            
            if (!empty($changes)) {
                 Audit::create([
                    'action' => 'update',
                    'user_id' => $user->id,
                    'module' => 'Auth',
                    'target_table' => 'users',
                    'target_id' => $user->id,
                    'meta' => ['action' => 'profile.update', 'changes' => $changes],
                    'logged_at' => now(),
                    'ip_address' => $ip,
                    'user_agent' => $userAgent
                ]);
            }
            
            return $user->fresh();
        });
    }
}
