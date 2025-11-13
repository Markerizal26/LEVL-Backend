<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class QueryFilter
{
    private array $allowedFilters = [];
    private array $allowedSorts = [];
    private string $defaultSort = 'id';
    private int $defaultPerPage = 15;
    private int $maxPerPage = 100;
    private array $params = [];
    private array $filterOperators = [
        'eq' => '=',
        'neq' => '!=',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'like' => 'like',
        'in' => 'in',
        'between' => 'between',
    ];

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function allowFilters(array $fields): self
    {
        $this->allowedFilters = $fields;
        return $this;
    }

    public function allowSorts(array $fields): self
    {
        $this->allowedSorts = $fields;
        return $this;
    }

    public function setDefaultSort(string $sort): self
    {
        $this->defaultSort = $sort;
        return $this;
    }

    public function setDefaultPerPage(int $perPage): self
    {
        $this->defaultPerPage = $perPage;
        return $this;
    }

    public function setMaxPerPage(int $maxPerPage): self
    {
        $this->maxPerPage = $maxPerPage;
        return $this;
    }

    public function apply(Builder $query)
    {
        $this->applyFilters($query);
        $this->applySorting($query);
        return $this->paginate($query);
    }

    public function applyFiltersOnly(Builder $query): Builder
    {
        $this->applyFilters($query);
        return $query;
    }

    public function applyFiltersAndSorting(Builder $query): Builder
    {
        $this->applyFilters($query);
        $this->applySorting($query);
        return $query;
    }

    private function applyFilters(Builder $query): void
    {
        $filters = $this->params['filter'] ?? [];

        if (is_string($filters) || !is_array($filters)) {
            $filters = [];
        }

        foreach ($filters as $field => $value) {
            if (empty($value) || !in_array($field, $this->allowedFilters, true)) {
                continue;
            }

            $this->applyFilterValue($query, $field, $value);
        }

        if (!empty($this->params['search'])) {
            $this->applySearch($query, $this->params['search']);
        }
    }

    private function applyFilterValue(Builder $query, string $field, $value): void
    {
        if (is_array($value)) {
            $hasOperator = false;
            foreach ($value as $v) {
                if (is_string($v) && str_contains($v, ':')) {
                    $hasOperator = true;
                    break;
                }
            }

            if (!$hasOperator && !empty($value)) {
                $query->whereIn($field, array_filter(array_map('trim', $value)));
            } else {
                foreach ($value as $v) {
                    $this->applyFilterWithOperator($query, $field, $v);
                }
            }

            return;
        }

        $this->applyFilterWithOperator($query, $field, $value);
    }

    private function applyFilterWithOperator(Builder $query, string $field, $value): void
    {
        if (!is_string($value)) {
            $value = (string) $value;
        }

        $value = trim($value);

        if (empty($value)) {
            return;
        }

        if (str_contains($value, ':')) {
            [$operator, $filterValue] = explode(':', $value, 2);
            $operator = strtolower(trim($operator));

            if (array_key_exists($operator, $this->filterOperators)) {
                $this->applyOperatorFilter($query, $field, $operator, $filterValue);
                return;
            }
        }

        $query->where($field, '=', $value);
    }

    private function applyOperatorFilter(Builder $query, string $field, string $operator, string $value): void
    {
        $sqlOperator = $this->filterOperators[$operator];

        match ($operator) {
            'in' => $query->whereIn($field, explode(',', $value)),
            'between' => $this->applyBetweenFilter($query, $field, $value),
            'like' => $query->where($field, 'like', $value),
            default => $query->where($field, $sqlOperator, $value),
        };
    }

    private function applyBetweenFilter(Builder $query, string $field, string $value): void
    {
        $parts = explode(',', $value);
        if (count($parts) === 2) {
            $query->whereBetween($field, [trim($parts[0]), trim($parts[1])]);
        }
    }

    protected function applySearch(Builder $query, string $search): void
    {
        if (property_exists($query->getModel(), 'searchable')) {
            $searchables = $query->getModel()->searchable ?? [];
            if (!empty($searchables)) {
                $query->where(function (Builder $sub) use ($search, $searchables) {
                    foreach ($searchables as $field) {
                        $sub->orWhere($field, 'like', "%{$search}%");
                    }
                });
            }
        }
    }

    private function applySorting(Builder $query): void
    {
        $sort = $this->params['sort'] ?? $this->defaultSort;
        if (empty($sort)) {
            $sort = $this->defaultSort;
        }

        $sort = trim((string) $sort);
        if (empty($sort)) {
            return;
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        if (!in_array($field, $this->allowedSorts, true)) {
            $direction = str_starts_with($this->defaultSort, '-') ? 'desc' : 'asc';
            $field = ltrim($this->defaultSort, '-');
        }

        $query->orderBy($field, $direction);
    }

    private function getPaginationParams(): array
    {
        $page = max(1, (int) ($this->params['page'] ?? 1));
        $perPage = max(1, min($this->maxPerPage, (int) ($this->params['per_page'] ?? $this->defaultPerPage)));
        return [$page, $perPage];
    }

    private function paginate(Builder $query)
    {
        [$page, $perPage] = $this->getPaginationParams();
        return $query->paginate($perPage, ['*'], 'page', $page)->appends($this->params);
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function isFilterAllowed(string $field): bool
    {
        return in_array($field, $this->allowedFilters, true);
    }

    public function isSortAllowed(string $field): bool
    {
        return in_array($field, $this->allowedSorts, true);
    }

    public function getAllowedFilters(): array
    {
        return $this->allowedFilters;
    }

    public function getAllowedSorts(): array
    {
        return $this->allowedSorts;
    }
}
