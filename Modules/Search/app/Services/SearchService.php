<?php

namespace Modules\Search\Services;

use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;
use Modules\Search\Contracts\Repositories\SearchHistoryRepositoryInterface;
use Modules\Search\Contracts\Services\SearchServiceInterface;
use Modules\Search\DTOs\SearchResultDTO;

class SearchService implements SearchServiceInterface
{
    public function __construct(
        private readonly SearchHistoryRepositoryInterface $historyRepository
    ) {}

    /**
     * Perform full-text search with filters.
     *
     * @param  string  $query  Search query
     * @param  array  $filters  Filter criteria
     * @param  array  $sort  Sorting options
     */
    public function search(string $query, array $filters = [], array $sort = []): SearchResultDTO
    {
        $startTime = microtime(true);

        // Start with base query
        $searchQuery = Course::search($query);

        // Apply filters using Scout's where method (field, value)
        foreach ($filters as $field => $value) {
            // Skip pagination parameters
            if (collect(['per_page', 'page'])->contains($field)) {
                continue;
            }

            if ($field === 'duration_estimate' && is_array($value)) {
                // Duration filter is not supported in the current schema
                // Skip for now as duration_estimate column doesn't exist
                continue;
            } elseif (is_array($value)) {
                // For array filters, apply each value with OR logic
                foreach ($value as $val) {
                    $searchQuery->where($field, $val);
                }
            } else {
                // Handle single value filters
                $searchQuery->where($field, $value);
            }
        }

        // Apply sorting
        $sortField = $sort['field'] ?? 'relevance';
        $sortDirection = $sort['direction'] ?? 'desc';

        if ($sortField !== 'relevance') {
            $searchQuery->orderBy($sortField, $sortDirection);
        }

        // Get paginated results
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        $results = $searchQuery->paginate($perPage, 'page', $page);

        $executionTime = microtime(true) - $startTime;

        return new SearchResultDTO(
            items: $results,
            query: $query,
            filters: $filters,
            sort: $sort,
            total: $results->total(),
            executionTime: $executionTime
        );
    }

    /**
     * Get autocomplete suggestions.
     *
     * @param  string  $query  Partial query
     * @param  int  $limit  Number of suggestions
     */
    public function getSuggestions(string $query, int $limit = 10): array
    {
        if (empty(trim($query))) {
            return [];
        }

        // Search for courses matching the partial query
        $courses = Course::search($query)
            ->take($limit)
            ->get();

        // Extract unique suggestions from titles
        $suggestions = $courses->pluck('title')->unique()->take($limit)->values()->toArray();

        return $suggestions;
    }

    /**
     * Save search query to history.
     */
    public function saveSearchHistory(User $user, string $query, array $filters = [], int $resultsCount = 0): void
    {
        // Don't save empty queries
        if (empty(trim($query))) {
            return;
        }

        // Check if the last search by this user is the same query
        $lastSearch = $this->historyRepository->getLastSearchByUser($user->id);

        // Avoid duplicate consecutive searches
        if ($lastSearch && $lastSearch->query === $query) {
            return;
        }

        $this->historyRepository->create([
            'user_id' => $user->id,
            'query' => $query,
            'filters' => $filters,
            'results_count' => $resultsCount,
        ]);
    }
}
