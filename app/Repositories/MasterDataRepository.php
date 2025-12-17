<?php

namespace App\Repositories;

use App\Models\MasterDataItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Spatie\QueryBuilder\AllowedSort;

class MasterDataRepository extends BaseRepository
{
  /**
   * Allowed filter keys.
   *
   * @var array<int, string>
   */
  protected array $allowedFilters = ["is_active", "is_system", "value", "label"];

  /**
   * Allowed sort fields.
   *
   * @var array<int, string>
   */
  protected array $allowedSorts = ["value", "label", "sort_order", "created_at", "updated_at"];

  /**
   * Default sort field.
   */
  protected string $defaultSort = "sort_order";

  protected function model(): string
  {
    return MasterDataItem::class;
  }

  /**
   * Get paginated master data by type with optional Scout search.
   *
   * Supports:
   * - filter[is_active], filter[is_system], filter[value], filter[label]
   * - filter[search] or search parameter for Scout/Meilisearch
   * - sort: value, label, sort_order, created_at, updated_at (prefix with - for desc)
   */
  public function paginateByType(
    string $type,
    array $params = [],
    int $perPage = 15,
  ): LengthAwarePaginator {
    $query = $this->query()->where("type", $type);

    // Handle Scout search if search parameter is provided
    $searchQuery = $params["search"] ?? (request("filter.search") ?? request("search"));

    if ($searchQuery && trim($searchQuery) !== "") {
      $ids = MasterDataItem::search($searchQuery)
        ->query(fn($q) => $q->where("type", $type))
        ->keys()
        ->toArray();

      if (!empty($ids)) {
        $query->whereIn("id", $ids);
      } else {
        $query->whereRaw("1 = 0");
      }
    }

    return $this->filteredPaginate(
      $query,
      $params,
      $this->allowedFilters,
      $this->allowedSorts,
      $this->defaultSort,
      $perPage,
    );
  }

  /**
   * Get all master data by type (no pagination) with optional Scout search.
   */
  public function allByType(string $type, array $params = []): Collection
  {
    $query = $this->query()->where("type", $type);

    // Handle Scout search if search parameter is provided
    $searchQuery = $params["search"] ?? (request("filter.search") ?? request("search"));

    if ($searchQuery && trim($searchQuery) !== "") {
      $ids = MasterDataItem::search($searchQuery)
        ->query(fn($q) => $q->where("type", $type))
        ->keys()
        ->toArray();

      if (!empty($ids)) {
        $query->whereIn("id", $ids);
      } else {
        return new Collection();
      }
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

  /**
   * Get all distinct types.
   */
  public function getTypes(array $params = []): SupportCollection
  {
    $search = trim((string)($params["search"] ?? request("search", "")));
    $filterIsCrud = $params["filter"]["is_crud"] ?? request()->input("filter.is_crud");
    $normalizedIsCrud = null;

    if ($filterIsCrud !== null) {
      $normalizedIsCrud = filter_var($filterIsCrud, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    $types = \Spatie\QueryBuilder\QueryBuilder::for(MasterDataItem::class)
      ->select("type")
      ->selectRaw("COUNT(*) as count")
      ->selectRaw("MAX(updated_at) as last_updated")
      ->when(
        $search !== "",
        fn($query) => $query->where("type", "like", "%{$search}%"),
      )
      ->groupBy("type")
      ->allowedSorts(
        "type",
        AllowedSort::field("key", "type"),
        AllowedSort::callback("label", function ($query, bool $descending) {
          $query->orderBy("type", $descending ? "desc" : "asc");
        }),
        "count",
        "last_updated",
      )
      ->defaultSort("type")
      ->get()
      ->map(function ($item) {
        $crudTypes = ["categories", "difficulty-levels", "content-types"];
        $labelMap = [
          "categories" => "Kategori",
          "difficulty-levels" => "Tingkat Kesulitan",
          "content-types" => "Tipe Konten",
        ];

        return [
          "key" => $item->type,
          "label" => $labelMap[$item->type] ?? ucwords(str_replace("-", " ", $item->type)),
          "count" => $item->count,
          "last_updated" => $item->last_updated,
          "is_crud" => in_array($item->type, $crudTypes),
        ];
      });

    if ($search !== "") {
      $searchLower = strtolower($search);
      $types = $types->filter(function ($item) use ($searchLower) {
        return str_contains(strtolower($item["key"]), $searchLower)
          || str_contains(strtolower($item["label"]), $searchLower);
      });
    }

    if ($normalizedIsCrud !== null) {
      $types = $types->filter(fn($item) => $item["is_crud"] === $normalizedIsCrud);
    }

    return $types->values();
  }

  /**
   * Find by ID within a type.
   */
  public function find(string $type, int $id): ?MasterDataItem
  {
    return MasterDataItem::where("type", $type)->where("id", $id)->first();
  }

  /**
   * Check if value exists in type.
   */
  public function valueExists(string $type, string $value, ?int $excludeId = null): bool
  {
    return MasterDataItem::where("type", $type)
      ->where("value", $value)
      ->when($excludeId, fn($q) => $q->where("id", "!=", $excludeId))
      ->exists();
  }
}
