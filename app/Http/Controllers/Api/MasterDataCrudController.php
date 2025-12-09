<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterDataStoreRequest;
use App\Http\Requests\MasterDataUpdateRequest;
use App\Services\MasterDataService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

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
    public function types()
    {
        $types = $this->service->getTypes()
            ->pluck('type')
            ->map(fn ($type) => [
                'key' => $type,
                'label' => ucwords(str_replace('-', ' ', $type)),
            ]);

        return $this->success(['items' => $types], 'Daftar tipe master data');
    }

    /**
     * Daftar Item Master Data
     *
     * @authenticated
     */
    public function index(Request $request, string $type)
    {
        $perPage = max(1, (int) $request->get('per_page', 15));

        if ($request->get('all') === 'true') {
            $data = $this->service->all($type);

            return $this->success(['items' => $data], "Daftar master data: {$type}");
        }

        $paginator = $this->service->paginate($type, $perPage);

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
        $validated = $request->validated();

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
        $validated = $request->validated();

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
