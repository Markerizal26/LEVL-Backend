<?php

namespace Modules\Schemes\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Unit;

class SchemesCacheService
{
    private const TTL_COURSE = 3600;      // 1 hour
    private const TTL_LISTING = 300;      // 5 minutes
    private const TTL_UNITS = 3600;       // 1 hour
    
    /**
     * Get course by ID with caching
     */
    public function getCourse(int $id): ?Course
    {
        return Cache::tags(['schemes', 'courses'])
            ->remember("course.{$id}", self::TTL_COURSE, function () use ($id) {
                return Course::with(['instructor', 'tags', 'category', 'media'])
                    ->find($id);
            });
    }
    
    /**
     * Get course by Slug with caching
     */
    public function getCourseBySlug(string $slug): ?Course
    {
        return Cache::tags(['schemes', 'courses'])
            ->remember("course.slug.{$slug}", self::TTL_COURSE, function () use ($slug) {
                return Course::where('slug', $slug)
                    ->with(['instructor', 'tags', 'category', 'media', 'units.lessons'])
                    ->first();
            });
    }
    
    /**
     * Get public course listing (cached by page & filters)
     */
    public function getPublicCourses(int $page, int $perPage, array $filters): LengthAwarePaginator
    {
        // Create a unique cache key based on filters
        $filterKey = md5(json_encode($filters));
        
        return Cache::tags(['schemes', 'courses', 'listing'])
            ->remember("courses.public.{$page}.{$perPage}.{$filterKey}", self::TTL_LISTING, function () use ($page, $perPage, $filters) {
                // Return null here to indicate cache miss (Service will handle query)
                return null;
            });
    }
    
    /**
     * Invalidate specific course cache
     */
    public function invalidateCourse(int $courseId, ?string $slug = null): void
    {
        Cache::tags(['schemes', 'courses'])->forget("course.{$courseId}");
        
        if ($slug) {
            Cache::tags(['schemes', 'courses'])->forget("course.slug.{$slug}");
        }
        
        // Also invalidate listings as course data changed
        Cache::tags(['schemes', 'listing'])->flush();
    }
    
    /**
     * Invalidate all course listings (e.g. new course published)
     */
    public function invalidateListings(): void
    {
        Cache::tags(['schemes', 'listing'])->flush();
    }
}
