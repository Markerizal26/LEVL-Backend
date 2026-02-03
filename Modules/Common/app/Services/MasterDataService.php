<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Common\Models\MasterDataItem;
use Modules\Common\Repositories\MasterDataRepository;
use Modules\Common\Support\MasterDataEnumMapper;

class MasterDataService
{
    private const CACHE_TAG = 'master_data';

    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly MasterDataRepository $repository,
        private readonly MasterDataEnumMapper $enumMapper,
        private readonly \Modules\Common\Support\MasterDataProcessor $processor
    ) {}

    public function get(string $type): array|Collection
    {
        $staticTypes = $this->enumMapper->getStaticTypes();

        if (isset($staticTypes[$type])) {
            return $staticTypes[$type]();
        }

        return Cache::tags([self::CACHE_TAG])->remember(
            "type:{$type}",
            self::CACHE_TTL,
            fn () => $this->repository->allByType($type, ['filter' => ['is_active' => true]])
        );
    }

    public function find(string $type, int $id): ?MasterDataItem
    {
        return $this->repository->find($type, $id);
    }

    public function paginate(string $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateByType($type, [], $perPage);
    }

    public function isCrudAllowed(string $type): bool
    {
        return ! $this->enumMapper->isStaticType($type);
    }

    public function getAvailableTypes(array $params = []): LengthAwarePaginator
    {
        $staticTypes = $this->buildStaticTypesList();
        $dbTypes = $this->buildDatabaseTypesList();
        $merged = $staticTypes->concat($dbTypes);

        return $this->processor->process($merged, $params);
    }

    public function create(string $type, array $data): MasterDataItem
    {
        $data['type'] = $type;

        return $this->repository->create($data);
    }

    public function update(string $type, int $id, array $data): MasterDataItem
    {
        $item = $this->repository->find($type, $id);

        if (! $item) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Master data item not found.');
        }

        return $this->repository->update($item, $data);
    }

    public function delete(string $type, int $id): bool
    {
        $item = $this->repository->find($type, $id);

        if (! $item) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Master data item not found.');
        }

        return $this->repository->delete($item);
    }

    private function buildStaticTypesList(): Collection
    {
        $staticTypes = $this->enumMapper->getStaticTypes();

        return collect(array_keys($staticTypes))->map(function ($key) use ($staticTypes) {
            $data = $staticTypes[$key]();
            $count = is_array($data) ? count($data) : $data->count();

            return [
                'type' => $key,
                'label' => __("messages.master_data.{$key}") ?? ucwords(str_replace('-', ' ', $key)),
                'is_crud' => false,
                'count' => $count,
                'last_updated' => null,
            ];
        });
    }

    private function buildDatabaseTypesList(): Collection
    {
        return $this->repository->getTypes()->map(function ($item) {
            $item = is_array($item) ? $item : $item->toArray();

            if (isset($item['key']) && ! isset($item['type'])) {
                $item['type'] = $item['key'];
                unset($item['key']);
            }

            return $item;
        });
    }

    public function extractQueryParams(array $query): array
    {
        return [
            'search' => $query['search'] ?? null,
            'sort' => $query['sort'] ?? null,
            'sort_order' => $query['sort_order'] ?? null,
            'page' => $query['page'] ?? null,
            'per_page' => $query['per_page'] ?? null,
            'filter' => $query['filter'] ?? null,
        ];
    }

    public function getValidationRules(bool $isUpdate = false): array
    {
        return [
            'value' => ($isUpdate ? 'sometimes|' : '').'required|string|max:255',
            'label' => ($isUpdate ? 'sometimes|' : '').'required|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'nullable|array',
        ];
    }
}
