<?php

declare(strict_types=1);

namespace Modules\Common\Repositories;

use Modules\Common\Models\MasterDataItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Spatie\QueryBuilder\AllowedSort;

class MasterDataRepository extends \App\Repositories\BaseRepository implements \Modules\Common\Contracts\Repositories\MasterDataRepositoryInterface
{
  protected array $allowedFilters = ["is_active", "is_system", "value", "label"];

  protected array $allowedSorts = ["value", "label", "sort_order", "created_at", "updated_at"];

  protected string $defaultSort = "sort_order";

  protected function model(): string
  {
    return MasterDataItem::class;
  }

  public function paginateByType(
    string $type,
    array $params = [],
    int $perPage = 15,
  ): LengthAwarePaginator {
    $query = $this->query()->where("type", $type);
    $searchQuery = $this->extractSearchQuery($params);

    $this->applySearchFilter($query, $searchQuery, $type);

    return $this->filteredPaginate(
      $query,
      $params,
      $this->allowedFilters,
      $this->allowedSorts,
      $this->defaultSort,
      $perPage,
    );
  }

  public function allByType(string $type, array $params = []): Collection
  {
    $query = $this->query()->where("type", $type);
    $searchQuery = $this->extractSearchQuery($params);

    if (! $this->applySearchFilter($query, $searchQuery, $type)) {
      return new Collection();
    }

    $this->applyFiltering(
      $query,
      $params,
      $this->allowedFilters,
      $this->allowedSorts,
      $this->defaultSort,
    );

    return $query->get();
  }

  public function getTypes(array $params = []): SupportCollection
  {
    $search = $this->extractSearch($params);
    $normalizedIsCrud = $this->extractIsCrudFilter($params);

    $types = $this->buildTypesQuery($search);
    $types = $this->applyPostQueryFilters($types, $search, $normalizedIsCrud);

    return $types->values();
  }

  public function find(string $type, int $id): ?MasterDataItem
  {
    return MasterDataItem::where("type", $type)->where("id", $id)->first();
  }

  public function valueExists(string $type, string $value, ?int $excludeId = null): bool
  {
    return MasterDataItem::where("type", $type)
      ->where("value", $value)
      ->when($excludeId, fn($q) => $q->where("id", "!=", $excludeId))
      ->exists();
  }

  private function extractSearch(array $params): string
  {
    return trim((string)($params["search"] ?? request("search", "")));
  }

  private function extractIsCrudFilter(array $params): ?bool
  {
    $filterIsCrud = $params["filter"]["is_crud"] ?? request()->input("filter.is_crud");
    
    if ($filterIsCrud === null) {
      return null;
    }

    return filter_var($filterIsCrud, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
  }

  private function buildTypesQuery(string $search): SupportCollection
  {
    return \Spatie\QueryBuilder\QueryBuilder::for(MasterDataItem::class)
      ->select("type")
      ->selectRaw("COUNT(*) as count")
      ->selectRaw("MAX(updated_at) as last_updated")
      ->when($search !== "", fn($query) => $query->where("type", "like", "%{$search}%"))
      ->groupBy("type")
      ->allowedSorts($this->getAllowedSorts())
      ->defaultSort("type")
      ->get()
      ->map(fn($item) => $this->transformTypeItem($item));
  }

  public function getAllowedSorts(): array
  {
    return [
      "type",
      AllowedSort::field("key", "type"),
      AllowedSort::callback("label", function ($query, bool $descending) {
        $query->orderBy("type", $descending ? "desc" : "asc");
      }),
      "count",
      "last_updated",
    ];
  }

  private function transformTypeItem($item): array
  {
    $labelMap = ["categories" => "Kategori", "tags" => "Tags"];

    return [
      "key" => $item->type,
      "label" => $labelMap[$item->type] ?? ucwords(str_replace("-", " ", $item->type)),
      "count" => $item->count,
      "last_updated" => $item->last_updated,
      "is_crud" => true,
    ];
  }

  private function applyPostQueryFilters(
    SupportCollection $types, 
    string $search, 
    ?bool $normalizedIsCrud
  ): SupportCollection {
    if ($search !== "") {
      $types = $this->filterBySearch($types, $search);
    }

    if ($normalizedIsCrud !== null) {
      $types = $types->filter(fn($item) => $item["is_crud"] === $normalizedIsCrud);
    }

    return $types;
  }

  private function filterBySearch(SupportCollection $types, string $search): SupportCollection
  {
    $searchLower = strtolower($search);
    
    return $types->filter(function ($item) use ($searchLower) {
      return str_contains(strtolower($item["key"]), $searchLower)
        || str_contains(strtolower($item["label"]), $searchLower);
    });
  }

  private function extractSearchQuery(array $params): string
  {
    return trim((string)($params["search"] ?? request("search")));
  }

  private function applySearchFilter($query, string $searchQuery, string $type): bool
  {
    if (empty($searchQuery)) {
      return true;
    }

    $ids = $this->searchIds($searchQuery, $type);

    if (empty($ids)) {
      $query->whereRaw("1 = 0");
      return false;
    }

    $query->whereIn("id", $ids);
    return true;
  }

  private function searchIds(string $searchQuery, string $type): array
  {
    return MasterDataItem::search($searchQuery)
      ->query(fn($q) => $q->where("type", $type))
      ->keys()
      ->toArray();
  }
}
