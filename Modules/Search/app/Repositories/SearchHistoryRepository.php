<?php

namespace Modules\Search\Repositories;

use Illuminate\Support\Collection;
use Modules\Search\Contracts\Repositories\SearchHistoryRepositoryInterface;
use Modules\Search\Models\SearchHistory;

class SearchHistoryRepository implements SearchHistoryRepositoryInterface
{
    public function findByUserId(int $userId, int $limit = 20): Collection
    {
        return SearchHistory::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): SearchHistory
    {
        return SearchHistory::create($data);
    }

    public function getLastSearchByUser(int $userId): ?SearchHistory
    {
        return SearchHistory::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function deleteById(int $id, int $userId): int
    {
        return SearchHistory::where('user_id', $userId)
            ->where('id', $id)
            ->delete();
    }

    public function deleteByUserId(int $userId): int
    {
        return SearchHistory::where('user_id', $userId)->delete();
    }
}
