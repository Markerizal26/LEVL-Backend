<?php

declare(strict_types=1);

namespace Modules\Common\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Common\Models\Category;

class CategoryRepository extends BaseRepository
{
    protected array $allowedFilters = [
        'name',
        'value',
        'description',
        'status',
    ];

    protected array $allowedSorts = [
        'name',
        'value',
        'status',
        'created_at',
        'updated_at',
    ];

    protected string $defaultSort = '-created_at';

    protected function model(): string
    {
        return Category::class;
    }

    public function paginate(array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        $searchQuery = $params['search'] ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = Category::search($searchQuery)->keys()->toArray();

            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $this->filteredPaginate(
            $query,
            $params,
            $this->allowedFilters,
            $this->allowedSorts,
            $this->defaultSort,
            $perPage
        );
    }

    public function all(array $params = []): Collection
    {
        $query = $this->query();

        $searchQuery = $params['search'] ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = Category::search($searchQuery)->keys()->toArray();

            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                return new Collection;
            }
        }

        $this->applyFiltering($query, $params, $this->allowedFilters, $this->allowedSorts, $this->defaultSort);

        return $query->get();
    }
}
