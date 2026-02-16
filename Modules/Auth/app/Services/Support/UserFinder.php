<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Auth\Contracts\UserAccessPolicyInterface;
use Modules\Auth\Models\User;
use Modules\Auth\Services\UserCacheService;
use Modules\Schemes\Models\CourseAdmin;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;
use Spatie\QueryBuilder\QueryBuilder;

class UserFinder
{
    public function __construct(
        private readonly UserAccessPolicyInterface $userAccessPolicy,
        private readonly UserCacheService $cacheService,
    ) {}

    public function paginate(User $authUser, int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));
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

        return cache()->tags(['auth', 'users'])->remember(
            "auth:users:index:{$authUser->id}:{$perPage}:" . md5(json_encode($filters)),
            300,
            function () use ($authUser, $cleanFilters, $search, $perPage) {
                $requestData = $cleanFilters;
                if (request()->has('include')) {
                    $requestData['include'] = request()->get('include');
                }
                $request = new Request($requestData);

                $query = QueryBuilder::for(User::class, $request)
                    ->select(['id', 'name', 'email', 'username', 'status', 'account_status', 'created_at', 'email_verified_at'])
                    ->with(['roles:id,name,guard_name']);

                if ($search && trim((string) $search) !== '') {
                    $query->search($search);
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
                            $query->search($value);
                        }
                    }),
                ])
                    ->allowedIncludes([
                        // Auth Module
                        'roles',
                        'privacySettings',
                        'enrollments',
                        'managedCourses',
                        // Gamification Module (milestones table has no user_id - global catalog only)
                        'gamificationStats',
                        'badges',
                        'challenges',
                        'challengeCompletions',
                        'points',
                        'levels',
                        'learningStreaks',
                        // Learning Module
                        'submissions',
                        'assignments',
                        'receivedOverrides',
                        'grantedOverrides',
                        // Forums Module (Post model doesn't exist - only Thread and Reply exist)
                        'threads',
                    ])
                    ->allowedSorts(['name', 'email', 'username', 'status', 'created_at'])
                    ->defaultSort('-created_at')
                    ->paginate($perPage);
            }
        );
    }

    public function showUser(User $authUser, int $userId, ?Request $request = null): User
    {
        $target = $this->cacheService->getUser($userId);

        if (! $target) {
            $query = QueryBuilder::for(User::class, $request ?? new Request())
                ->with(['roles:id,name,guard_name'])
                ->allowedIncludes([
                    // Auth Module
                    'roles',
                    'privacySettings',
                    'enrollments',
                    'managedCourses',
                    // Gamification Module (milestones table has no user_id - global catalog only)
                    'gamificationStats',
                    'badges',
                    'challenges',
                    'challengeCompletions',
                    'points',
                    'levels',
                    'learningStreaks',
                    // Learning Module
                    'submissions',
                    'assignments',
                    'receivedOverrides',
                    'grantedOverrides',
                    // Forums Module (Post model doesn't exist - only Thread and Reply exist)
                    'threads',
                ])
                ->where('id', $userId);
            
            $target = $query->firstOrFail();
            
            // Always load course when enrollments is loaded (either via include or already loaded)
            if ($target->relationLoaded('enrollments')) {
                $target->loadMissing('enrollments.course');
            }
        } else {
            // If cached, load roles if not already loaded
            if (! $target->relationLoaded('roles')) {
                $target->load('roles:id,name,guard_name');
            }
            
            // Load other requested includes if specified
            if ($request && $request->has('include')) {
                $includes = explode(',', $request->get('include'));
                $includes = array_map('trim', $includes);
                $includes = array_filter($includes); // Remove empty strings
                
                $allowedIncludes = [
                    'roles', 'privacySettings', 'enrollments', 'managedCourses',
                    'gamificationStats', 'badges', 'challenges', 'challengeCompletions',
                    'points', 'levels', 'learningStreaks',
                    'submissions', 'assignments', 'receivedOverrides', 'grantedOverrides',
                    'threads',
                ];
                
                // Validate includes - throw error if any invalid includes are requested
                $invalidIncludes = array_diff($includes, $allowedIncludes);
                if (!empty($invalidIncludes)) {
                    throw new InvalidIncludeQuery(
                        Collection::make($invalidIncludes),
                        Collection::make($allowedIncludes)
                    );
                }
                
                // Load valid includes
                if (!empty($includes)) {
                    $target->load($includes);
                    
                    // Always load course when enrollments is loaded
                    if (in_array('enrollments', $includes) && $target->relationLoaded('enrollments')) {
                        $target->loadMissing('enrollments.course');
                    }
                }
            }
        }

        if (! $authUser->can('view', $target)) {
            throw new AuthorizationException(__('messages.auth.no_access_to_user'));
        }

        return $target;
    }
}
