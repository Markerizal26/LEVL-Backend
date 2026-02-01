<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Auth\Contracts\UserAccessPolicyInterface;
use Modules\Auth\Models\User;
use Modules\Auth\Services\UserCacheService;
use Modules\Schemes\Models\CourseAdmin;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserFinder
{
    public function __construct(
        private readonly UserAccessPolicyInterface $userAccessPolicy,
        private readonly UserCacheService $cacheService,
    ) {}

    public function listUsers(User $authUser, int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $filters = $search ? ['search' => $search] : [];
        return $this->listUsersForIndex($authUser, $filters, $perPage);
    }

    public function listUsersForIndex(User $authUser, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (! $authUser->can('viewAny', User::class)) {
            throw new AuthorizationException(__('messages.unauthorized'));
        }

        $search = data_get($filters, 'search');
        
        $cleanFilters = Arr::except($filters, ['search']);

        $request = new Request($cleanFilters);

        $query = QueryBuilder::for(User::class, $request)
            ->select(['id', 'name', 'email', 'username', 'status', 'account_status', 'created_at', 'email_verified_at', 'is_password_set'])
            ->with(['roles:id,name,guard_name', 'media:id,model_type,model_id,collection_name,file_name,disk']);

        if ($search && trim((string) $search) !== '') {
            $ids = User::search($search)->keys()->toArray();
            $query->whereIn('id', $ids);
        }

        if ($authUser->hasRole('Admin') && ! $authUser->hasRole('Superadmin')) {
            $managedCourseIds = CourseAdmin::query()
                ->where('user_id', $authUser->id)
                ->pluck('course_id')
                ->unique();

            $query->where(function (Builder $q) use ($managedCourseIds) {
                $q->whereHas('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'Admin');
                })
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
        $target = $this->cacheService->getUser($userId);

        if (! $target) {
            $target = User::findOrFail($userId);
        }

        if (! $authUser->can('view', $target)) {
            throw new AuthorizationException(__('messages.auth.no_access_to_user'));
        }

        return $target;
    }
}
