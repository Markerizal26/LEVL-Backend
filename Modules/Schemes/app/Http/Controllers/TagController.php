<?php

declare(strict_types=1);

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Http\Requests\TagRequest;
use Modules\Schemes\Http\Resources\TagResource;
use Modules\Schemes\Models\Tag;
use Modules\Schemes\Services\TagService;

class TagController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(private readonly TagService $service) {}

    public function index(Request $request)
    {
        $filters = $request->except(['page', 'per_page']);
        $data = $this->service->list($filters, (int) $request->query('per_page', 15));

        if ($data instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            return $this->paginateResponse($data->through(fn($tag) => new TagResource($tag)));
        }
        return $this->success(TagResource::collection($data));
    }

    public function store(TagRequest $request)
    {
        $this->authorize('create', Tag::class);
        $result = $this->service->handleCreate($request->validated());
        
        return $result instanceof \Illuminate\Support\Collection
            ? $this->success(TagResource::collection($result), __('messages.tags.created'))
            : $this->created(new TagResource($result), __('messages.tags.created'));
    }

    public function show(Tag $tag)
    {
        return $this->success(new TagResource($tag));
    }

    public function update(TagRequest $request, Tag $tag)
    {
        $this->authorize('update', $tag);
        $updated = $this->service->update($tag->id, $request->validated());

        return $this->success(new TagResource($updated), __('messages.tags.updated'));
    }

    public function destroy(Tag $tag)
    {
        $this->authorize('delete', $tag);
        $this->service->delete($tag->id);

        return $this->success([], __('messages.tags.deleted'));
    }
}
