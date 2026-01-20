<?php

namespace Modules\Content\Services;

use App\Exceptions\BusinessException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Content\Contracts\Repositories\AnnouncementRepositoryInterface;
use Modules\Content\Contracts\Services\AnnouncementServiceInterface;
use Modules\Content\DTOs\CreateAnnouncementDTO;
use Modules\Content\DTOs\UpdateAnnouncementDTO;
use Modules\Content\Events\AnnouncementPublished;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\ContentRevision;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AnnouncementService implements AnnouncementServiceInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $repository
    ) {}

    public function getForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        $query = QueryBuilder::for(Announcement::class)
            ->where(function ($q) use ($user) {
                $q->where('target_type', 'all')
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('target_type', 'specific_users')
                            ->whereHas('targetUsers', fn ($q3) => $q3->where('user_id', $user->id));
                    });
            })
            ->allowedFilters([
                AllowedFilter::exact('course_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('priority'),
                AllowedFilter::exact('target_type'),
            ])
            ->allowedIncludes(['author', 'course'])
            ->allowedSorts(['published_at', 'created_at', 'priority'])
            ->defaultSort('-published_at');

        return $query->paginate($perPage);
    }

    public function getForCourse(int $courseId, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        $query = QueryBuilder::for(Announcement::class)
            ->where('course_id', $courseId)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('priority'),
                AllowedFilter::exact('target_type'),
            ])
            ->allowedIncludes(['author', 'course'])
            ->allowedSorts(['published_at', 'created_at', 'priority'])
            ->defaultSort('-published_at');

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Announcement
    {
        return $this->repository->findWithRelations($id);
    }

    public function create(CreateAnnouncementDTO $dto, User $author, ?int $courseId = null): Announcement
    {
        return DB::transaction(function () use ($dto, $author, $courseId) {
            $data = array_merge($dto->toArrayWithoutNull(), [
                'author_id' => $author->id,
                'course_id' => $courseId,
                'target_type' => 'all',
                'priority' => $dto->priority ?? 'normal',
            ]);

            return $this->repository->create($data);
        });
    }

    public function update(Announcement $announcement, UpdateAnnouncementDTO $dto, User $editor): Announcement
    {
        return DB::transaction(function () use ($announcement, $dto, $editor) {
            $announcement->saveRevision($editor);

            return $this->repository->update($announcement, $dto->toArrayWithoutNull());
        });
    }

    public function delete(Announcement $announcement, User $user): bool
    {
        return $this->repository->delete($announcement, $user->id);
    }

    /**
     * @throws BusinessException
     */
    public function publish(Announcement $announcement): Announcement
    {
        if ($announcement->status === 'published') {
            throw new BusinessException(__('messages.announcements.already_published'));
        }

        return DB::transaction(function () use ($announcement) {
            $this->repository->update($announcement, [
                'status' => 'published',
                'published_at' => now(),
                'scheduled_at' => null,
            ]);

            event(new AnnouncementPublished($announcement->fresh()));

            return $announcement->fresh();
        });
    }

    /**
     * @throws BusinessException
     */
    public function schedule(Announcement $announcement, \Carbon\Carbon $publishAt): Announcement
    {
        if ($publishAt->isPast()) {
            throw new BusinessException(__('messages.content.schedule_future_required'));
        }

        $this->repository->update($announcement, [
            'status' => 'scheduled',
            'scheduled_at' => $publishAt,
        ]);

        return $announcement->fresh();
    }

    public function getScheduledForPublishing(): Collection
    {
        return $this->repository->getScheduledForPublishing();
    }

    public function getUnreadCount(User $user): int
    {
        return $this->repository->getUnreadCount($user);
    }
}
