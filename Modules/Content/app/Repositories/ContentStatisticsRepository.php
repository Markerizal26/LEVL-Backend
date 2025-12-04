<?php

namespace Modules\Content\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\News;
use Modules\Enrollments\Enums\EnrollmentStatus;

class ContentStatisticsRepository
{
    /**
     * Get view count for a specific content.
     */
    public function getViewCount(string $contentType, int $contentId): int
    {
        $model = $contentType === 'announcement' ? Announcement::class : News::class;

        return $model::where('id', $contentId)->value('views_count') ?? 0;
    }

    /**
     * Get read count for a specific announcement.
     */
    public function getReadCount(int $announcementId): int
    {
        return DB::table('content_reads')
            ->where('readable_type', Announcement::class)
            ->where('readable_id', $announcementId)
            ->count();
    }

    /**
     * Calculate read rate for an announcement.
     */
    public function calculateReadRate(Announcement $announcement): float
    {
        $targetUsers = $this->getTargetUsersCount($announcement);

        if ($targetUsers === 0) {
            return 0.0;
        }

        $readCount = $this->getReadCount($announcement->id);

        return ($readCount / $targetUsers) * 100;
    }

    /**
     * Get count of target users for an announcement.
     */
    protected function getTargetUsersCount(Announcement $announcement): int
    {
        if ($announcement->target_type === 'all') {
            return User::where('status', UserStatus::Active->value)->count();
        }

        if ($announcement->target_type === 'role') {
            return User::whereHas('roles', function ($q) use ($announcement) {
                $q->where('name', $announcement->target_value);
            })->count();
        }

        if ($announcement->target_type === 'course' && $announcement->course_id) {
            return DB::table('enrollments')
                ->where('course_id', $announcement->course_id)
                ->where('status', EnrollmentStatus::Active->value)
                ->distinct('user_id')
                ->count('user_id');
        }

        return 0;
    }

    /**
     * Get list of users who haven't read an announcement.
     */
    public function getUnreadUsers(Announcement $announcement): Collection
    {
        $readUserIds = DB::table('content_reads')
            ->where('readable_type', Announcement::class)
            ->where('readable_id', $announcement->id)
            ->pluck('user_id');

        $query = User::whereNotIn('id', $readUserIds)
            ->where('status', UserStatus::Active->value);

        if ($announcement->target_type === 'role') {
            $query->whereHas('roles', function ($q) use ($announcement) {
                $q->where('name', $announcement->target_value);
            });
        }

        if ($announcement->target_type === 'course' && $announcement->course_id) {
            $query->whereHas('enrollments', function ($q) use ($announcement) {
                $q->where('course_id', $announcement->course_id)
                    ->where('status', EnrollmentStatus::Active->value);
            });
        }

        return $query->select('id', 'name', 'email')->get();
    }

    /**
     * Get statistics for all announcements.
     */
    public function getAnnouncementStatistics(array $filters = []): Collection
    {
        $query = Announcement::published()
            ->with(['author'])
            ->withCount('reads');

        if (isset($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('published_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('published_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('published_at', 'desc')->get();
    }

    /**
     * Get statistics for all news.
     */
    public function getNewsStatistics(array $filters = []): Collection
    {
        $query = News::published()
            ->with(['author', 'categories'])
            ->withCount('reads');

        if (isset($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('content_categories.id', $filters['category_id']);
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('published_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('published_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('views_count', 'desc')->get();
    }

    /**
     * Get most viewed news in a time period.
     */
    public function getMostViewedNews(int $days = 30, int $limit = 10): Collection
    {
        return News::published()
            ->where('published_at', '>=', now()->subDays($days))
            ->with(['author', 'categories'])
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
