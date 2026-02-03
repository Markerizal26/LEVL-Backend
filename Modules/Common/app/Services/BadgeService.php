<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Meilisearch\Exceptions\ApiException;
use Modules\Common\Contracts\Services\BadgeServiceInterface;
use Modules\Common\Repositories\BadgeRepository;
use Modules\Gamification\Models\Badge;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BadgeService implements BadgeServiceInterface
{
    public function __construct(private readonly BadgeRepository $repository) {}

    public function paginate(int $perPage = 15, array $params = []): LengthAwarePaginator
    {
        $query = Badge::query();
        
        $this->applySearch($query, $params);

        return QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('code'),
                AllowedFilter::partial('name'),
                AllowedFilter::exact('type'),
            ])
            ->allowedSorts(['id', 'code', 'name', 'type', 'threshold', 'created_at', 'updated_at'])
            ->defaultSort('-created_at')
            ->paginate(max(1, $perPage));
    }

    public function create(array $data, array $files = []): Badge
    {
        return DB::transaction(function () use ($data, $files) {
            $badge = $this->repository->create($data);

            if (!empty($data['rules'])) {
                $this->syncRules($badge->id, $data['rules']);
            }

            if (isset($files['icon'])) {
                $badge->addMedia($files['icon'])->toMediaCollection('icon');
            }

            return $badge->fresh();
        });
    }

    public function createOrFind(string $code, array $data = [], ?string $iconPath = null): Badge
    {
        $existingBadge = Badge::where('code', $code)->first();
        if ($existingBadge) {
            return $existingBadge;
        }

        $badgeData = array_merge([
            'code' => $code,
            'name' => ucfirst(str_replace('_', ' ', $code)),
            'description' => $data['description'] ?? 'Badge for milestone',
            'type' => $data['type'] ?? 'milestone',
            'threshold' => $data['threshold'] ?? null,
        ], $data);

        $badge = $this->repository->create($badgeData);

        if ($iconPath && file_exists($iconPath)) {
            $badge->addMedia($iconPath)->toMediaCollection('icon');
        }

        return $badge->fresh();
    }

    public function find(int $id): ?Badge
    {
        return $this->repository->findById($id);
    }

    public function update(int $id, array $data, array $files = []): ?Badge
    {
        $badge = $this->repository->findById($id);

        if (! $badge) {
            return null;
        }

        return DB::transaction(function () use ($badge, $data, $files) {
            $updated = $this->repository->update($badge, $data);

            if (isset($data['rules'])) {
                $this->syncRules($badge->id, $data['rules']);
            }

            if (isset($files['icon'])) {
                $updated->clearMediaCollection('icon');
                $updated->addMedia($files['icon'])->toMediaCollection('icon');
            }

            return $updated->fresh();
        });
    }

    public function delete(int $id): bool
    {
        $badge = $this->repository->findById($id);

        if (! $badge) {
            return false;
        }

        return DB::transaction(function () use ($badge) {
            $badge->clearMediaCollection('icon');

            return $this->repository->delete($badge);
        });
    }

    private function applySearch(\Illuminate\Database\Eloquent\Builder $query, array $params): void
    {
        $searchQuery = $params['search'] ?? request('search');

        if (! $searchQuery || trim($searchQuery) === '') {
            return;
        }

        try {
            $ids = Badge::search($searchQuery)->keys()->toArray();
            $query->whereIn('id', $ids ?: [0]);
        } catch (ApiException $e) {
            if (! str_contains($e->getMessage(), 'not found')) {
                throw $e;
            }
            $query->whereRaw('1 = 0');
        }
    }

    private function syncRules(int $badgeId, array $rules): void
    {
        \Modules\Gamification\Models\BadgeRule::where('badge_id', $badgeId)->delete();

        foreach ($rules as $rule) {
            \Modules\Gamification\Models\BadgeRule::create([
                'badge_id' => $badgeId,
                'criterion' => $rule['criterion'],
                'operator' => $rule['operator'],
                'value' => $rule['value'],
            ]);
        }
    }
}
