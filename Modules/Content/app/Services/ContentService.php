<?php

namespace Modules\Content\Services;

use App\Contracts\Services\ContentServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Content\DTOs\CreateAnnouncementDTO;
use Modules\Content\DTOs\CreateNewsDTO;
use Modules\Content\DTOs\UpdateAnnouncementDTO;
use Modules\Content\DTOs\UpdateNewsDTO;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\News;
use Modules\Content\Repositories\AnnouncementRepository;
use Modules\Content\Repositories\NewsRepository;

class ContentService implements ContentServiceInterface
{
    protected AnnouncementRepository $announcementRepository;

    protected NewsRepository $newsRepository;

    public function __construct(
        AnnouncementRepository $announcementRepository,
        NewsRepository $newsRepository
    ) {
        $this->announcementRepository = $announcementRepository;
        $this->newsRepository = $newsRepository;
    }

    /**
     * Create announcement from DTO or array.
     *
     * @throws \Exception
     */
    public function createAnnouncement(CreateAnnouncementDTO|array $data, User $author): Announcement
    {
        // Convert array to DTO if needed
        if (is_array($data)) {
            $data = CreateAnnouncementDTO::from($data);
        }

        return DB::transaction(function () use ($data, $author) {
            $announcementData = array_merge($data->toModelArray(), [
                'author_id' => $author->id,
            ]);
            
            // dd($announcementData);

            return $this->announcementRepository->create($announcementData);
        });
    }

    /**
     * Create news from DTO or array.
     *
     * @throws \Exception
     */
    public function createNews(CreateNewsDTO|array $data, User $author): News
    {
        // Convert array to DTO if needed
        if (is_array($data)) {
            $data = CreateNewsDTO::from($data);
        }

        return DB::transaction(function () use ($data, $author) {
            $newsData = array_merge($data->toModelArray(), [
                'author_id' => $author->id,
            ]);

            return $this->newsRepository->create($newsData);
        });
    }

    /**
     * Update announcement from DTO or array.
     */
    public function updateAnnouncement(Announcement $announcement, UpdateAnnouncementDTO|array $data, User $editor): Announcement
    {
        // Convert array to DTO if needed
        if (is_array($data)) {
            $data = UpdateAnnouncementDTO::from($data);
        }

        return DB::transaction(function () use ($announcement, $data, $editor) {
            $announcement->saveRevision($editor);

            return $this->announcementRepository->update($announcement, $data->toModelArray());
        });
    }

    /**
     * Update news from DTO or array.
     */
    public function updateNews(News $news, UpdateNewsDTO|array $data, User $editor): News
    {
        // Convert array to DTO if needed
        if (is_array($data)) {
            $data = UpdateNewsDTO::from($data);
        }

        return DB::transaction(function () use ($news, $data, $editor) {
            $news->saveRevision($editor);

            return $this->newsRepository->update($news, $data->toModelArray());
        });
    }

    public function publishContent($content): bool
    {
        return DB::transaction(function () use ($content) {
            $content->update([
                'status' => 'published',
                'published_at' => now(),
                'scheduled_at' => null,
            ]);

            if ($content instanceof Announcement) {
                event(new \Modules\Content\Events\AnnouncementPublished($content));
            } elseif ($content instanceof News) {
                event(new \Modules\Content\Events\NewsPublished($content));
            }

            return true;
        });
    }

    public function scheduleContent($content, \Carbon\Carbon $publishAt): bool
    {
        if ($publishAt->isPast()) {
            throw new \Exception(__('messages.content.schedule_future_required'));
        }

        return $content->update([
            'status' => 'scheduled',
            'scheduled_at' => $publishAt,
        ]);
    }

    public function cancelSchedule($content): bool
    {
        return $content->update([
            'status' => 'draft',
            'scheduled_at' => null,
        ]);
    }

    public function deleteContent($content, User $user): bool
    {
        if ($content instanceof Announcement) {
            return $this->announcementRepository->delete($content, $user->id);
        } elseif ($content instanceof News) {
            return $this->newsRepository->delete($content, $user->id);
        }

        return false;
    }

    public function getAnnouncementsForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->announcementRepository->getAnnouncementsForUser($user, $filters);
    }

    public function getNewsFeed(array $filters = []): LengthAwarePaginator
    {
        return $this->newsRepository->getNewsFeed($filters);
    }

    public function searchContent(string $query, string $type = 'all', array $filters = []): LengthAwarePaginator
    {
        if ($type === 'news' || $type === 'all') {
            return $this->newsRepository->searchNews($query, $filters);
        }

        return $this->newsRepository->searchNews($query, $filters);
    }

    public function markAsRead($content, User $user): void
    {
        if ($content instanceof Announcement) {
            $content->markAsReadBy($user);
        } elseif ($content instanceof News) {
            $content->reads()->firstOrCreate([
                'user_id' => $user->id,
                'readable_type' => News::class,
                'readable_id' => $content->id,
            ]);
        }
    }

    public function incrementViews($content): void
    {
        if ($content instanceof News) {
            $content->incrementViews();
        } elseif ($content instanceof Announcement) {
            $content->increment('views_count');
        }
    }

    public function getTrendingNews(int $limit = 10): Collection
    {
        return $this->newsRepository->getTrendingNews($limit);
    }

    public function getFeaturedNews(int $limit = 5): Collection
    {
        return $this->newsRepository->getFeaturedNews($limit);
    }
}
