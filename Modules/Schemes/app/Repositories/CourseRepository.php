<?php

namespace Modules\Schemes\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Schemes\Models\Course;

class CourseRepository
{
    private array $allowedSortFields = ['id', 'code', 'title', 'created_at', 'updated_at', 'published_at'];

    public function query(): Builder
    {
        return Course::query();
    }

    public function findById(int $id): ?Course
    {
        return Course::query()->find($id);
    }

    public function findBySlug(string $slug): ?Course
    {
        return Course::query()->where('slug', $slug)->first();
    }

    public function create(array $attributes): Course
    {
        return Course::create($attributes);
    }

    public function update(Course $course, array $attributes): Course
    {
        $course->fill($attributes);
        $course->save();

        return $course;
    }

    public function delete(Course $course): void
    {
        $course->delete();
    }

    private function parseCategoryFilter($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $trim = trim($value);
            if ($trim !== '' && ($trim[0] === '[' || str_starts_with($trim, '%5B'))) {
                // try decode JSON or URL-encoded JSON-like arrays
                $decoded = json_decode($trim, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
                $urldec = urldecode($trim);
                $decoded = json_decode($urldec, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }

            // fallback single value string like "3"
            return [$value];
        }

        return [];
    }

    private function parseArrayFilter($value): array
    {

        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $trim = trim($value);
            if ($trim !== '' && ($trim[0] === '[' || str_starts_with($trim, '%5B'))) {
                $decoded = json_decode($trim, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
                $urldec = urldecode($trim);
                $decoded = json_decode($urldec, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }

            return [$value];
        }

        return [];
    }

    private function applySorting(Builder $query, ?string $sort): void
    {
        if ($sort === null || $sort === '') {
            $query->orderBy('title', 'asc');

            return;
        }
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');
        if (! in_array($field, $this->allowedSortFields, true)) {
            $query->orderBy('title', 'asc');

            return;
        }
        $query->orderBy($field, $direction);
    }

    public function paginate(array $params, int $perPage = 15): LengthAwarePaginator
    {
        $query = Course::query();

        $filters = $params['filter'] ?? [];

        if (! empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['level'])) {
            $query->where('level_tag', $filters['level']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']); // okupasi | kluster
        }

        if (! empty($filters['category'])) {
            $cat = $filters['category'];
            $catIds = $this->parseCategoryFilter($cat);
            $query->whereIn('category_id', array_filter($catIds, fn ($v) => (int) $v > 0));
        }

        // Legacy support
        if (! empty($params['category_id'])) {
            $catIds = is_array($params['category_id']) ? $params['category_id'] : [$params['category_id']];
            $query->whereIn('category_id', array_filter($catIds, fn ($v) => (int) $v > 0));
        }

        // Tags filter from filter[tag] as array JSON; legacy params['tags'] still supported
        if (! empty($filters['tag'])) {
            $tags = $this->parseArrayFilter($filters['tag']);
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags_json', $tag);
            }
        } elseif (! empty($params['tags']) && is_array($params['tags'])) {
            foreach ($params['tags'] as $tag) {
                $query->whereJsonContains('tags_json', $tag);
            }
        }

        if (! empty($params['search'])) {
            $keyword = trim((string) $params['search']);
            $query->where(function (Builder $sub) use ($keyword) {
                $sub->where('title', 'like', "%{$keyword}%")
                    ->orWhere('short_desc', 'like', "%{$keyword}%");
            });
        }

        $this->applySorting($query, $params['sort'] ?? null);

        return $query->paginate($perPage)->appends($params);
    }

    public function list(array $params): Collection
    {
        $query = Course::query();

        $filters = $params['filter'] ?? [];
        if (! empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['level'])) {
            $query->where('level_tag', $filters['level']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['category'])) {
            $cat = $filters['category'];
            $catIds = $this->parseCategoryFilter($cat);
            $query->whereIn('category_id', array_filter($catIds, fn ($v) => (int) $v > 0));
        }
        if (! empty($filters['tag'])) {
            $tags = $this->parseArrayFilter($filters['tag']);
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags_json', $tag);
            }
        } elseif (! empty($params['tags']) && is_array($params['tags'])) {
            foreach ($params['tags'] as $tag) {
                $query->whereJsonContains('tags_json', $tag);
            }
        }
        if (! empty($params['search'])) {
            $keyword = trim((string) $params['search']);
            $query->where(function (Builder $sub) use ($keyword) {
                $sub->where('title', 'like', "%{$keyword}%")
                    ->orWhere('short_desc', 'like', "%{$keyword}%");
            });
        }

        $this->applySorting($query, $params['sort'] ?? null);

        return $query->get();
    }
}
