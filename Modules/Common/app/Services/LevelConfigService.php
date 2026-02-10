<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

use Modules\Common\Contracts\Services\LevelConfigServiceInterface;
use Modules\Common\Models\LevelConfig;
use Modules\Common\Repositories\LevelConfigRepository;
use Modules\Gamification\Models\Badge;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LevelConfigService implements LevelConfigServiceInterface
{
    public function __construct(
        private readonly LevelConfigRepository $repository,
        private readonly BadgeService $badgeService,
    ) {}

    public function paginate(int $perPage = 15, array $params = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        $query = LevelConfig::query();

        $searchQuery = $params['search'] ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            $query->search($searchQuery);
        }

        return QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('level'),
                AllowedFilter::partial('name'),
            ])
            ->allowedSorts(['id', 'level', 'name', 'xp_required', 'created_at', 'updated_at'])
            ->defaultSort('level')
            ->paginate($perPage);
    }

    public function create(array $data): LevelConfig
    {
        return DB::transaction(function () use ($data) {
            $levelConfig = $this->repository->create($data);
            $this->syncBadgesFromRewards($levelConfig->id, $data['rewards'] ?? []);
            return $levelConfig->fresh();
        });
    }

    public function find(int $id): ?LevelConfig
    {
        return $this->repository->findById($id);
    }

    public function update(int $id, array $data): ?LevelConfig
    {
        $config = $this->repository->findById($id);

        if (! $config) {
            return null;
        }

        return DB::transaction(function () use ($config, $data) {
            $updated = $this->repository->update($config, $data);
            $this->syncBadgesFromRewards($updated->id, $data['rewards'] ?? []);
            return $updated->fresh();
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $config = $this->repository->findById($id);
            if (! $config) {
                return false;
            }
            return $this->repository->delete($config);
        });
    }

    private function syncBadgesFromRewards(int $levelConfigId, array $rewards): void
    {
        if (empty($rewards)) {
            return;
        }

        foreach ($rewards as $reward) {
            if ($reward['type'] !== 'badge') {
                continue;
            }

            $badgeCode = $reward['value'] ?? null;
            if (! $badgeCode) {
                continue;
            }

            $this->badgeService->createOrFind($badgeCode, [
                'description' => 'Badge earned upon reaching level milestone',
                'type' => 'milestone',
            ]);
        }
    }
}
