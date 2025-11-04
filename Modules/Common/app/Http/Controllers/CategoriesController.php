<?php

namespace Modules\Common\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Common\Http\Requests\CategoryStoreRequest;
use Modules\Common\Http\Requests\CategoryUpdateRequest;
use Modules\Common\Models\Category;

class CategoriesController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Category::query();

        if ($search = $request->string('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('value', 'like', "%{$search}%");
        }
        if ($status = $request->string('filter.status')) {
            $query->where('status', $status);
        }

        $sort = (string) $request->get('sort', '');
        if ($sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            if (in_array($field, ['created_at', 'name'], true)) {
                $query->orderBy($field, $direction);
            } else {
                $query->latest();
            }
        } else {
            $query->latest();

        }

        $perPage = max(1, (int) $request->get('per_page', 15));

        return $this->paginateResponse($query->paginate($perPage)->appends($request->query()));
    }

    public function store(CategoryStoreRequest $request)
    {
        $category = Category::create($request->validated());

        return $this->created(['category' => $category], 'Kategori dibuat');
    }

    public function show(int $category)
    {
        $c = Category::find($category);
        if (! $c) {
            return $this->error('Kategori tidak ditemukan', 404);
        }

        return $this->success(['category' => $c]);
    }

    public function update(CategoryUpdateRequest $request, int $category)
    {
        $c = Category::find($category);
        if (! $c) {
            return $this->error('Kategori tidak ditemukan', 404);
        }
        $c->fill($request->validated())->save();

        return $this->success(['category' => $c], 'Kategori diperbarui');
    }

    public function destroy(int $category)
    {
        $c = Category::find($category);
        if (! $c) {
            return $this->error('Kategori tidak ditemukan', 404);
        }
        $c->delete();

        return $this->success([], 'Kategori dihapus');
    }
}
