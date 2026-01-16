<?php

declare(strict_types=1);

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Http\Requests\TagRequest;
use Modules\Schemes\Http\Resources\TagResource;
use Modules\Schemes\Models\Tag;
use Modules\Schemes\Services\TagService;

class TagController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TagService $service) {}

    public function index(Request $request)
    {
        $paginator = $this->service->list(
            $request->query('filter', []),
            (int) $request->query('per_page', 15)
        );

        $paginator->setCollection(TagResource::collection($paginator->getCollection()));
        return $this->paginateResponse($paginator);
    }

    public function store(TagRequest $request)
    {
        $this->authorize('create', Tag::class);
        $tag = $this->service->create($request->validated());

        return $this->created(new TagResource($tag), __('messages.tags.created'));
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
