<?php

declare(strict_types=1);

namespace Modules\Common\Traits;

use Illuminate\Database\Eloquent\Builder;

trait PgSearchable
{
    public function scopeSearch(Builder $query, string $term, ?float $threshold = null): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $columns = $this->getSearchableColumns();

        if (empty($columns)) {
            return $query;
        }

        $term = trim($term);
        
        if ($threshold === null) {
            $len = strlen($term);
            if ($len <= 3) {
                $threshold = 0.5;
            } elseif ($len <= 5) {
                $threshold = 0.4;
            } else {
                $threshold = 0.3;
            }
        }

        return $query->where(function (Builder $subQuery) use ($columns, $term, $threshold) {
            foreach ($columns as $column) {
                $subQuery->orWhere($column, 'ILIKE', "%{$term}%");
                $subQuery->orWhereRaw("similarity($column, ?) > ?", [$term, $threshold]);
            }
        });
    }

    public function getSearchableColumns(): array
    {
        return property_exists($this, 'searchable_columns') ? $this->searchable_columns : [];
    }
}