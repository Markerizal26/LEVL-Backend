<?php

declare(strict_types=1);

namespace Modules\Common\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as PaginatorInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;

class MasterDataProcessor
{
    public function process(Collection $collection, array $params): LengthAwarePaginator
    {
        $filtered = $this->applyFilters($collection, $params);
        $sorted = $this->applySorting($filtered, $params);

        return $this->paginate($sorted, $params);
    }

    private function applyFilters(Collection $collection, array $params): Collection
    {
        $collection = $this->filterByCrud($collection, $params);
        
        return $this->filterBySearch($collection, $params);
    }

    private function filterByCrud(Collection $collection, array $params): Collection
    {
        if (! isset($params['filter']['is_crud'])) {
            return $collection;
        }

        $isCrud = filter_var($params['filter']['is_crud'], FILTER_VALIDATE_BOOLEAN);

        return $collection->filter(fn ($item) => $item['is_crud'] === $isCrud);
    }

    private function filterBySearch(Collection $collection, array $params): Collection
    {
        if (empty($params['search'])) {
            return $collection;
        }

        $search = strtolower($params['search']);

        return $collection->filter(function ($item) use ($search) {
            return str_contains(strtolower($item['type']), $search) || 
                   str_contains(strtolower($item['label']), $search);
        });
    }

    private function applySorting(Collection $collection, array $params): Collection
    {
        $allowedSorts = ['type', 'label', 'count', 'last_updated'];
        $defaultSort = 'label';
        
        $requestedSorts = $params['sort'] ?? $defaultSort;
        $sorts = is_array($requestedSorts) ? $requestedSorts : explode(',', (string) $requestedSorts);

        foreach (array_reverse($sorts) as $sort) {
            $sort = trim($sort);
            $descending = str_starts_with($sort, '-');
            $field = $descending ? substr($sort, 1) : $sort;

            if (in_array($field, $allowedSorts, true)) {
                $collection = $descending
                    ? $collection->sortByDesc($field, SORT_NATURAL | SORT_FLAG_CASE)
                    : $collection->sortBy($field, SORT_NATURAL | SORT_FLAG_CASE);
            }
        }

        return $collection;
    }

    private function paginate(Collection $collection, array $params): LengthAwarePaginator
    {
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);

        return new PaginatorInstance(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            ['path' => Request::url(), 'query' => $params]
        );
    }
}
