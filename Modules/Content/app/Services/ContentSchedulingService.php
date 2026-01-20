<?php

namespace Modules\Content\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\News;
use Modules\Content\Repositories\AnnouncementRepository;
use Modules\Content\Repositories\NewsRepository;

class ContentSchedulingService
{
    protected AnnouncementRepository $announcementRepository;

    protected NewsRepository $newsRepository;

    protected ContentNotificationService $notificationService;

    public function __construct(
        AnnouncementRepository $announcementRepository,
        NewsRepository $newsRepository,
        ContentNotificationService $notificationService
    ) {
        $this->announcementRepository = $announcementRepository;
        $this->newsRepository = $newsRepository;
        $this->notificationService = $notificationService;
    }

    /**
     * @throws \Exception
     */
    public function schedulePublication($content, Carbon $publishAt): bool
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

    public function publishScheduledContent(): int
    {
        $publishedCount = 0;

        // Get scheduled announcements
        $scheduledAnnouncements = $this->announcementRepository->getScheduledForPublishing();

        foreach ($scheduledAnnouncements as $announcement) {
            if ($this->publishContent($announcement)) {
                $publishedCount++;
                Log::info("Published scheduled announcement: {$announcement->id}");
            }
        }

        // Get scheduled news
        $scheduledNews = $this->newsRepository->getScheduledForPublishing();

        foreach ($scheduledNews as $news) {
            if ($this->publishContent($news)) {
                $publishedCount++;
                Log::info("Published scheduled news: {$news->id}");
            }
        }

        return $publishedCount;
    }

    protected function publishContent($content): bool
    {
        return DB::transaction(function () use ($content) {
            $content->update([
                'status' => 'published',
                'published_at' => now(),
                'scheduled_at' => null,
            ]);

            $this->notificationService->notifyScheduledPublication($content);

            if ($content instanceof Announcement) {
                event(new \Modules\Content\Events\AnnouncementPublished($content));
            } elseif ($content instanceof News) {
                event(new \Modules\Content\Events\NewsPublished($content));
            }

            return true;
        });
    }

    public function getScheduledCount(): array
    {
        $announcementCount = Announcement::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->count();

        $newsCount = News::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->count();

        return [
            'announcements' => $announcementCount,
            'news' => $newsCount,
            'total' => $announcementCount + $newsCount,
        ];
    }
}
