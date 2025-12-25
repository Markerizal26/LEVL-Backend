<?php

namespace Modules\Schemes\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use Modules\Schemes\Contracts\Repositories\TagRepositoryInterface;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Tag;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TagService
{
    public function __construct(
        private TagRepositoryInterface $repository
    ) {}

    public function list(int $perPage = 0): LengthAwarePaginator|Collection
    {
        $query = $this->buildQuery();

        if ($perPage > 0) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    private function buildQuery(): QueryBuilder
    {
        $searchQuery = request('filter.search');

        $builder = QueryBuilder::for(Tag::class);

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = Tag::search($searchQuery)->keys()->toArray();
            $builder->whereIn('id', $ids);
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::partial('slug'),
                AllowedFilter::partial('description'),
            ])
            ->allowedSorts(['name', 'slug', 'created_at', 'updated_at'])
            ->defaultSort('name');
    }

    public function create(array $data): Tag
    {
        $name = trim((string) ($data['name'] ?? ''));

        return $this->firstOrCreateByName($name);
    }

    /**
     * @param  array<int, string>  $names
     * @return \Illuminate\Support\Collection<int, Tag>
     */
    public function createMany(array $names): BaseCollection
    {
        return BaseCollection::make($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->map(fn ($name) => $this->firstOrCreateByName($name))
            ->values();
    }

    public function update(int $id, array $data): ?Tag
    {
        $tag = $this->repository->findById($id);
        if (! $tag) {
            return null;
        }

        $payload = $this->preparePayload($data, $tag->id, $tag->slug, $tag->name);
        $tag->fill($payload);
        $tag->save();

        return $tag;
    }

    public function delete(int $id): bool
    {
        $tag = $this->repository->findById($id);
        if (! $tag) {
            return false;
        }

        $tag->courses()->detach();

        return (bool) $tag->delete();
    }

    public function syncCourseTags(Course $course, array $tags): void
    {
        $tagIds = $this->resolveTagIds($tags);

        $course->tags()->sync($tagIds);

        $names = Tag::query()->whereIn('id', $tagIds)->pluck('name')->unique()->values()->toArray();
        $course->tags_json = $names;
        $course->save();
    }

    private function preparePayload(array $data, ?int $ignoreId = null, ?string $currentSlug = null, ?string $currentName = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        return [
            'name' => $name,
        ];
    }

    private function firstOrCreateByName(string $name): Tag
    {
        $existing = Tag::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return $existing;
        }

        $payload = $this->preparePayload(['name' => $name]);

        return $this->repository->create($payload);
    }

    private function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        return $this->findUniqueSlug($slug, $slug, 1, $ignoreId);
    }

    private function findUniqueSlug(string $base, string $slug, int $counter, ?int $ignoreId): string
    {
        $exists = Tag::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists();

        if (!$exists) {
            return $slug;
        }

        return $this->findUniqueSlug($base, "{$base}-{$counter}", $counter + 1, $ignoreId);
    }

    /**
     * @param  array<string|int>  $tags
     * @return array<int>
     */
    private function resolveTagIds(array $tags): array
    {
        return BaseCollection::make($tags)
            ->map(function ($tag) {
                if (is_numeric($tag)) {
                    return Tag::query()->where('id', (int) $tag)->value('id');
                }

                $value = trim((string) $tag);
                if ($value === '') {
                    return null;
                }

                $bySlug = Tag::query()
                    ->where('slug', Str::slug($value))
                    ->orWhere('slug', $value)
                    ->first();

                if ($bySlug) {
                    return $bySlug->id;
                }

                $byName = Tag::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($value)])->first();
                if ($byName) {
                    return $byName->id;
                }

                $payload = $this->preparePayload(['name' => $value]);

                return $this->repository->create($payload)->id;
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }
}
