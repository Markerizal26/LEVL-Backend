<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

use Modules\Common\Contracts\Services\ChallengeManagementServiceInterface;
use Modules\Common\Repositories\ChallengeManagementRepository;
use Modules\Gamification\Models\Challenge;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ChallengeManagementService
implements ChallengeManagementServiceInterface
{
    public function __construct(private readonly ChallengeManagementRepository $repository) {}

    public function paginate(int $perPage = 15, array $params = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        $query = Challenge::query()->with('badge');

        $searchQuery = $params['search'] ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            $query->search($searchQuery);
        }

        return QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('title'),
                AllowedFilter::exact('type'),
            ])
            ->allowedSorts(['id', 'title', 'type', 'points_reward', 'start_at', 'end_at', 'created_at', 'updated_at'])
            ->defaultSort('-created_at')
            ->paginate($perPage);
    }

    public function create(array $data): Challenge
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function find(int $id): ?Challenge
    {
        return $this->repository->findById($id);
    }

    public function update(int $id, array $data): ?Challenge
    {
        $achievement = $this->repository->findById($id);
        if (! $achievement) {
            return null;
        }
        return DB::transaction(function () use ($achievement, $data) {
            return $this->repository->update($achievement, $data);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $achievement = $this->repository->findById($id);
            if (! $achievement) {
                return false;
            }
            return $this->repository->delete($achievement);
        });
    }
}
