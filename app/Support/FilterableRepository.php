<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

trait FilterableRepository
{
    public function filter(Builder $query, array $params): QueryFilter
    {
        return new QueryFilter($params);
    }

    public function applyFiltering(Builder $query, array $params, array $allowedFilters = [], array $allowedSorts = [], string $defaultSort = 'id'): Builder
    {
        return $this->filter($query, $params)
            ->allowFilters($allowedFilters)
            ->allowSorts($allowedSorts)
            ->setDefaultSort($defaultSort)
            ->applyFiltersAndSorting($query);
    }

    public function filteredPaginate(Builder $query, array $params, array $allowedFilters = [], array $allowedSorts = [], string $defaultSort = 'id', int $perPage = 15)
    {
        return $this->filter($query, $params)
            ->allowFilters($allowedFilters)
            ->allowSorts($allowedSorts)
            ->setDefaultSort($defaultSort)
            ->setDefaultPerPage($perPage)
            ->apply($query);
    }
}
