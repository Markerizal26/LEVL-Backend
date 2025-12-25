<?php

namespace Modules\Search\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Search\Models\SearchHistory;

interface SearchHistoryRepositoryInterface
{
    /**
     * Find search history by user ID
     */
    public function findByUserId(int $userId, int $limit = 20): Collection;

    /**
     * Create a new search history entry
     */
    public function create(array $data): SearchHistory;

    /**
     * Get user's last search
     */
    public function getLastSearchByUser(int $userId): ?SearchHistory;

    /**
     * Delete a specific search history entry by ID and user ID
     */
    public function deleteById(int $id, int $userId): int;

    /**
     * Delete all search history for a user
     */
    public function deleteByUserId(int $userId): int;
}
