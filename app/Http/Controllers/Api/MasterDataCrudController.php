<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterDataStoreRequest;
use App\Http\Requests\MasterDataUpdateRequest;
use App\Services\MasterDataService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @tags Data Master
 */
class MasterDataCrudController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly MasterDataService $service) {}

    /**
     * Daftar Tipe Master Data
     *
     * @authenticated
     */
    public function types(Request $request)
    {
        $perPage = max(1, min((int) $request->get('per_page', 15), 100));
        
        $paginator = $this->service->getTypesPaginated($request->all(), $perPage);

        return $this->paginateResponse($paginator, 'Daftar tipe master data');
    }

    /**
     * Daftar Item Master Data
     *
     * @authenticated
     */
    public function index(Request $request, string $type)
    {
        $perPage = max(1, (int) $request->get('per_page', 15));

        $query = \Spatie\QueryBuilder\QueryBuilder::for(\App\Models\MasterDataItem::class)
            ->where('type', $type)
            ->allowedFilters(['label', 'value', \Spatie\QueryBuilder\AllowedFilter::exact('is_active')])
            ->allowedSorts(['label', 'value', 'created_at', 'updated_at', 'sort_order'])
            ->defaultSort('sort_order');

        // Handle Search
        if ($request->has('search') && $request->search) {
            $query->where('label', 'like', "%{$request->search}%");
        }

        if ($request->get('all') === 'true') {
            $data = $query->get();

            return $this->success(['items' => $data], "Daftar master data: {$type}");
        }

        $paginator = $query->paginate($perPage);

        return $this->paginateResponse($paginator, "Daftar master data: {$type}");
    }

    /**
     * Detail Master Data
     *
     * @authenticated
     */
    public function show(string $type, int $id)
    {
        $item = $this->service->find($type, $id);

        if (! $item) {
            return $this->error('Master data tidak ditemukan', 404);
        }

        return $this->success(['item' => $item], 'Detail master data');
    }

    /**
     * Buat Master Data Baru
     *
     * @authenticated
     */
    public function store(MasterDataStoreRequest $request, string $type)
    {
        // Check if this is a CRUD type (database-backed) or enum type
        $allTypes = $this->service->getTypes();
        $typeInfo = $allTypes->firstWhere('key', $type);
        
        if (!$typeInfo || ($typeInfo['is_crud'] ?? true) === false) {
            return $this->error('Item untuk tipe enum tidak dapat dibuat', 403);
        }

        $validated = $request->validated();

        Log::info('Master data create request', [
            'type' => $type,
            'user_id' => auth()->id(),
            'data' => $validated,
        ]);

        // Check for duplicate
        if ($this->service->valueExists($type, $validated['value'])) {
            return $this->error('Value sudah ada untuk tipe ini', 422);
        }

        $item = $this->service->create($type, $validated);

        return $this->created(['item' => $item], 'Master data berhasil ditambahkan');
    }

    /**
     * Perbarui Master Data
     *
     * @authenticated
     */
    public function update(MasterDataUpdateRequest $request, string $type, int $id)
    {
        // Check if this is a CRUD type (database-backed) or enum type
        $allTypes = $this->service->getTypes();
        $typeInfo = $allTypes->firstWhere('key', $type);
        
        if (!$typeInfo || ($typeInfo['is_crud'] ?? true) === false) {
            return $this->error('Item dari tipe enum tidak dapat diedit', 403);
        }

        $validated = $request->validated();

        Log::info('Master data update request', [
            'type' => $type,
            'id' => $id,
            'user_id' => auth()->id(),
            'data' => $validated,
        ]);

        // Check for duplicate value if changing
        if (isset($validated['value'])) {
            if ($this->service->valueExists($type, $validated['value'], $id)) {
                return $this->error('Value sudah ada untuk tipe ini', 422);
            }
        }

        $updated = $this->service->update($type, $id, $validated);

        if (! $updated) {
            return $this->error('Master data tidak ditemukan', 404);
        }

        return $this->success(['item' => $updated], 'Master data berhasil diperbarui');
    }

    /**
     * Hapus Master Data
     *
     * @authenticated
     */
    public function destroy(string $type, int $id)
    {
        // Check if this is a CRUD type (database-backed) or enum type
        $allTypes = $this->service->getTypes();
        $typeInfo = $allTypes->firstWhere('key', $type);
        
        if (!$typeInfo || ($typeInfo['is_crud'] ?? true) === false) {
            return $this->error('Item dari tipe enum tidak dapat dihapus', 403);
        }

        Log::info('Master data delete request', [
            'type' => $type,
            'id' => $id,
            'user_id' => auth()->id(),
        ]);

        $result = $this->service->delete($type, $id);

        if ($result === 'not_found') {
            return $this->error('Master data tidak ditemukan', 404);
        }

        if ($result === 'system_protected') {
            return $this->error('Master data sistem tidak dapat dihapus', 403);
        }

        return $this->success([], 'Master data berhasil dihapus');
    }
}
