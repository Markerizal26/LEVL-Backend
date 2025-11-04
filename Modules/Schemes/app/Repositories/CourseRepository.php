<?php

namespace Modules\Schemes\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Schemes\Entities\Course;

class CourseRepository
{
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

    public function paginate(array $params, int $perPage = 15): LengthAwarePaginator
    {
        $query = Course::query();

        if (! empty($params['visibility'])) {
            $query->where('visibility', $params['visibility']);
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['level']) || ! empty($params['level_tag'])) {
            $level = $params['level'] ?? $params['level_tag'];
            $query->where('level_tag', $level);
        }

        if (! empty($params['category'])) {
            $query->where('category', $params['category']);
        }

        if (! empty($params['tags']) && is_array($params['tags'])) {
            foreach ($params['tags'] as $tag) {
                $query->whereJsonContains('tags_json', $tag);
            }
        }

        if (! empty($params['q'])) {
            $keyword = trim((string) $params['q']);
            $query->where(function (Builder $sub) use ($keyword) {
                $sub->where('title', 'like', "%{$keyword}%")
                    ->orWhere('short_desc', 'like', "%{$keyword}%");
            });
        }

        if (! empty($params['sort'])) {

            [$field, $direction] = array_pad(explode(':', $params['sort'], 2), 2, 'desc');
            $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($field, $direction);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage)->appends($params);
    }

    public function list(array $params): Collection
    {
        $query = Course::query();

        if (! empty($params['visibility'])) {
            $query->where('visibility', $params['visibility']);
        }
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['level']) || ! empty($params['level_tag'])) {
            $level = $params['level'] ?? $params['level_tag'];
            $query->where('level_tag', $level);
        }
        if (! empty($params['category'])) {
            $query->where('category', $params['category']);
        }
        if (! empty($params['tags']) && is_array($params['tags'])) {
            foreach ($params['tags'] as $tag) {
                $query->whereJsonContains('tags_json', $tag);
            }
        }
        if (! empty($params['q'])) {
            $keyword = trim((string) $params['q']);
            $query->where(function (Builder $sub) use ($keyword) {
                $sub->where('title', 'like', "%{$keyword}%")
                    ->orWhere('short_desc', 'like', "%{$keyword}%");
            });
        }

        return $query->get();
    }
}
