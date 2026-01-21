<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Auth\Enums\UserStatus;
use Modules\Common\Enums\CategoryStatus;
use Modules\Common\Enums\SettingType;
use Modules\Common\Models\MasterDataItem;
use Modules\Common\Repositories\MasterDataRepository;
use Modules\Content\Enums\ContentStatus;
use Modules\Content\Enums\Priority;
use Modules\Content\Enums\TargetType;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Enums\ProgressStatus;
use Modules\Gamification\Enums\BadgeType;
use Modules\Gamification\Enums\ChallengeAssignmentStatus;
use Modules\Gamification\Enums\ChallengeCriteriaType;
use Modules\Gamification\Enums\ChallengeType;
use Modules\Gamification\Enums\PointReason;
use Modules\Gamification\Enums\PointSourceType;
use Modules\Grading\Enums\GradeStatus;
use Modules\Grading\Enums\SourceType;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\SubmissionStatus;
use Modules\Learning\Enums\SubmissionType;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Enums\NotificationFrequency;
use Modules\Notifications\Enums\NotificationType;
use Modules\Schemes\Enums\ContentType;
use Modules\Schemes\Enums\CourseStatus;
use Modules\Schemes\Enums\CourseType;
use Modules\Schemes\Enums\EnrollmentType;
use Modules\Schemes\Enums\LevelTag;
use Modules\Schemes\Enums\ProgressionMode;
use Spatie\Permission\Models\Role;

class MasterDataService
{
    private const CACHE_PREFIX = 'master_data:';
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly MasterDataRepository $repository
    ) {}

    public function get(string $type): array|\Illuminate\Support\Collection
    {
        $map = $this->getMap();

        if (isset($map[$type])) {
            return $map[$type]();
        }

        return Cache::remember(
            self::CACHE_PREFIX . $type,
            self::CACHE_TTL,
            fn() => $this->repository->allByType($type, ['filter' => ['is_active' => true]])
        );
    }

    public function find(string $type, int $id): ?MasterDataItem
    {
        return $this->repository->find($type, $id);
    }

    public function paginate(string $type, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->repository->paginateByType($type, [], $perPage);
    }

    public function isCrudAllowed(string $type): bool
    {
        return !array_key_exists($type, $this->getMap());
    }

    public function getAvailableTypes(array $params = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $map = $this->getMap();
        
        $staticTypes = collect(array_keys($map))->map(function ($key) use ($map) {
            $data = $map[$key]();
            $count = is_array($data) ? count($data) : $data->count();
            
            return [
                'type' => $key,
                'label' => __("messages.master_data.{$key}") ?? ucwords(str_replace('-', ' ', $key)),
                'is_crud' => false,
                'count' => $count,
                'last_updated' => null,
            ];
        });

        $dbTypes = $this->repository->getTypes()->map(function ($item) {
            $item = is_array($item) ? $item : $item->toArray();
            if (isset($item['key']) && !isset($item['type'])) {
                $item['type'] = $item['key'];
                unset($item['key']);
            }
            return $item;
        });

        $merged = $staticTypes->concat($dbTypes);

        if (isset($params['filter']['is_crud'])) {
            $isCrud = filter_var($params['filter']['is_crud'], FILTER_VALIDATE_BOOLEAN);
            $merged = $merged->filter(function ($item) use ($isCrud) {
                return $item['is_crud'] === $isCrud;
            });
        }

        if (!empty($params['search'])) {
            $search = strtolower($params['search']);
            $merged = $merged->filter(function ($item) use ($search) {
                return str_contains(strtolower($item['type']), $search) ||
                       str_contains(strtolower($item['label']), $search);
            });
        }

            $allowedSorts = ['type', 'label', 'count', 'last_updated'];
            $defaultSort = 'label';
            $sortParam = $params['sort'] ?? $defaultSort;
            $requestedSorts = is_array($sortParam) ? $sortParam : explode(',', (string) $sortParam);
        
            $validSorts = [];
            foreach ($requestedSorts as $sort) {
                $sort = trim($sort);
                $descending = str_starts_with($sort, '-');
                $field = $descending ? substr($sort, 1) : $sort;

                if (in_array($field, $allowedSorts, true)) {
                    $validSorts[] = $descending ? '-' . $field : $field;
                }
            }

            if (empty($validSorts)) {
                $validSorts[] = $defaultSort;
            }

            foreach (array_reverse($validSorts) as $sort) {
                $descending = str_starts_with($sort, '-');
                $field = $descending ? substr($sort, 1) : $sort;

                $merged = $descending
                    ? $merged->sortByDesc($field, SORT_NATURAL | SORT_FLAG_CASE)
                    : $merged->sortBy($field, SORT_NATURAL | SORT_FLAG_CASE);
            }

        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        
        return new \Illuminate\Pagination\LengthAwarePaginator(
                $merged->forPage($page, $perPage)->values(),
            $merged->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Support\Facades\Request::url(), 'query' => $params]
        );
    }

    public function create(string $type, array $data): MasterDataItem
    {
        $data['type'] = $type;
        $item = $this->repository->create($data);
        return $item;
    }

    public function update(string $type, int $id, array $data): MasterDataItem
    {
        $item = $this->repository->find($type, $id);
        
        if (!$item) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Master data item not found.");
        }

        $updated = $this->repository->update($item, $data);
        return $updated;
    }

    public function delete(string $type, int $id): bool
    {
        $item = $this->repository->find($type, $id);

        if (!$item) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Master data item not found.");
        }

        $deleted = $this->repository->delete($item);
        return $deleted;
    }

    private function transformEnum(string $enumClass): array
    {
        return array_map(
            fn($case) => [
                "value" => $case->value,
                "label" => $case->label(),
            ],
            $enumClass::cases(),
        );
    }

    private function getMap(): array
    {
        return [
            "user-status" => fn() => $this->transformEnum(UserStatus::class),
            "roles" => fn() => Role::all()->map(fn($role) => [
                "value" => $role->name,
                "label" => __("enums.roles." . strtolower($role->name)),
            ])->toArray(),
            "course-status" => fn() => $this->transformEnum(CourseStatus::class),
            "course-types" => fn() => $this->transformEnum(CourseType::class),
            "enrollment-types" => fn() => $this->transformEnum(EnrollmentType::class),
            "level-tags" => fn() => $this->transformEnum(LevelTag::class),
            "progression-modes" => fn() => $this->transformEnum(ProgressionMode::class),
            "content-types" => fn() => $this->transformEnum(ContentType::class),
            "enrollment-status" => fn() => $this->transformEnum(EnrollmentStatus::class),
            "progress-status" => fn() => $this->transformEnum(ProgressStatus::class),
            "assignment-status" => fn() => $this->transformEnum(AssignmentStatus::class),
            "submission-status" => fn() => $this->transformEnum(SubmissionStatus::class),
            "submission-types" => fn() => $this->transformEnum(SubmissionType::class),
            "content-status" => fn() => $this->transformEnum(ContentStatus::class),
            "priorities" => fn() => $this->transformEnum(Priority::class),
            "target-types" => fn() => $this->transformEnum(TargetType::class),
            "challenge-types" => fn() => $this->transformEnum(ChallengeType::class),
            "challenge-assignment-status" => fn() => $this->transformEnum(ChallengeAssignmentStatus::class),
            "challenge-criteria-types" => fn() => $this->transformEnum(ChallengeCriteriaType::class),
            "badge-types" => fn() => $this->transformEnum(BadgeType::class),
            "point-source-types" => fn() => $this->transformEnum(PointSourceType::class),
            "point-reasons" => fn() => $this->transformEnum(PointReason::class),
            "notification-types" => fn() => $this->transformEnum(NotificationType::class),
            "notification-channels" => fn() => $this->transformEnum(NotificationChannel::class),
            "notification-frequencies" => fn() => $this->transformEnum(NotificationFrequency::class),
            "grade-status" => fn() => $this->transformEnum(GradeStatus::class),
            "grade-source-types" => fn() => $this->transformEnum(SourceType::class),
            "category-status" => fn() => $this->transformEnum(CategoryStatus::class),
            "setting-types" => fn() => $this->transformEnum(SettingType::class),
        ];
    }
}
