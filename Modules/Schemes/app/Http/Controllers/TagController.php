<?php

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Http\Requests\TagRequest;
use Modules\Schemes\Models\Tag;
use Modules\Schemes\Services\TagService;

/**
 * @tags Skema & Kursus
 */
class TagController extends Controller
{
    use ApiResponse;

    public function __construct(private TagService $service) {}

    /**
     * Daftar Tag
     *
     *
     * @summary Daftar Tag
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":[{"id":1,"name":"Example Tag"}],"meta":{"current_page":1,"last_page":5,"per_page":15,"total":75},"links":{"first":"...","last":"...","prev":null,"next":"..."}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->query('per_page', 0));

        $result = $this->service->list($perPage);

        if ($result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            return $this->paginateResponse($result, 'Daftar tag berhasil diambil.');
        }

        return $this->success([
            'items' => $result,
        ], 'Daftar tag berhasil diambil.');
    }

    /**
     * Buat Tag Baru
     *
     *
     * @summary Buat Tag Baru
     *
     * @response 201 scenario="Success" {"success":true,"message":"Tag berhasil dibuat.","data":{"id":1,"name":"New Tag"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal.","errors":{"field":["Field wajib diisi."]}}
     * @authenticated
     */
    public function store(TagRequest $request)
    {
        $validated = $request->validated();

        if (! empty($validated['names'])) {
            $tags = $this->service->createMany($validated['names']);

            return $this->created(['tags' => $tags], 'Tag berhasil dibuat.');
        }

        $tag = $this->service->create($validated);

        return $this->created(['tag' => $tag], 'Tag berhasil dibuat.');
    }

    /**
     * Detail Tag
     *
     *
     * @summary Detail Tag
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Tag"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Tag tidak ditemukan."}
     * @authenticated
     */
    public function show(Tag $tag)
    {
        return $this->success(['tag' => $tag]);
    }

    /**
     * Perbarui Tag
     *
     *
     * @summary Perbarui Tag
     *
     * @response 200 scenario="Success" {"success":true,"message":"Tag berhasil diperbarui.","data":{"id":1,"name":"Updated Tag"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Tag tidak ditemukan."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal.","errors":{"field":["Field wajib diisi."]}}
     * @authenticated
     */
    public function update(TagRequest $request, Tag $tag)
    {
        $validated = $request->validated();
        unset($validated['names']);

        $updated = $this->service->update($tag->id, $validated);

        if (! $updated) {
            return $this->error('Tag tidak ditemukan.', 404);
        }

        return $this->success(['tag' => $updated], 'Tag berhasil diperbarui.');
    }

    /**
     * Hapus Tag
     *
     *
     * @summary Hapus Tag
     *
     * @response 200 scenario="Success" {"success":true,"message":"Tag berhasil dihapus.","data":[]}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Tag tidak ditemukan."}
     * @authenticated
     */
    public function destroy(Tag $tag)
    {
        $deleted = $this->service->delete($tag->id);

        if (! $deleted) {
            return $this->error('Tag tidak ditemukan.', 404);
        }

        return $this->success([], 'Tag berhasil dihapus.');
    }
}
