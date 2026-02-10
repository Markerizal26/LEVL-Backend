<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Auth\Contracts\Repositories\UserBulkRepositoryInterface;
use Modules\Auth\Contracts\Services\UserBulkServiceInterface;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Jobs\ExportUsersToEmailJob;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\CourseAdmin;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserBulkService implements UserBulkServiceInterface
{
    public function __construct(private UserBulkRepositoryInterface $repository) {}

    public function export(User $authUser, array $data): void
    {
        $userIds = $data['user_ids'] ?? [];
        $recipientEmail = $data['email'] ?? $authUser->email;

        if (empty($userIds)) {
            $userIds = $this->resolveUserIdsFromFilters($authUser, $data['filter'] ?? [], $data['search'] ?? null);
        }

        if (! empty($userIds)) {
            ExportUsersToEmailJob::dispatch($userIds, $recipientEmail);
        }
    }

    private function resolveUserIdsFromFilters(User $authUser, array $filters, ?string $search = null): array
    {
        $isSuperadmin = $authUser->hasRole('Superadmin');
        $isAdmin = $authUser->hasRole('Admin');

        $query = QueryBuilder::for(User::class, new Request(['filter' => $filters]))
            ->select('id');

        if ($search && trim($search) !== '') {
            $query->search($search);
        }

        if ($isAdmin && ! $isSuperadmin) {
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
        ])
            ->pluck('id')
            ->toArray();
    }

    public function bulkActivate(array $userIds, int $changedBy): int
    {
        $updated = $this->repository->bulkUpdateStatus($userIds, UserStatus::Active->value);

        $this->logStatusChanges($userIds, $changedBy, UserStatus::Active->value);

        return $updated;
    }

    public function bulkDeactivate(array $userIds, int $changedBy, int $currentUserId): int
    {
        $userIds = array_diff($userIds, [$currentUserId]);

        if (empty($userIds)) {
            throw new \InvalidArgumentException(__('messages.auth.cannot_deactivate_self'));
        }

        $updated = $this->repository->bulkUpdateStatus($userIds, UserStatus::Inactive->value);

        $this->logStatusChanges($userIds, $changedBy, UserStatus::Inactive->value);

        return $updated;
    }

    public function bulkDelete(array $userIds, int $currentUserId): int
    {
        $userIds = array_diff($userIds, [$currentUserId]);

        if (empty($userIds)) {
            throw new \InvalidArgumentException(__('messages.auth.cannot_delete_self'));
        }

        return $this->repository->bulkDelete($userIds);
    }

    private function logStatusChanges(array $userIds, int $changedBy, string $newStatus): void
    {
        foreach ($userIds as $userId) {
            $targetUser = $this->repository->findById($userId);
            if ($targetUser) {
                $targetUser->logActivity('status_changed', [
                    'changed_by' => $changedBy,
                    'old_status' => $targetUser->status->value,
                    'new_status' => $newStatus,
                ]);
            }
        }
    }
}
