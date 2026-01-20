<?php

namespace Modules\Content\Services;

use App\Exceptions\BusinessException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Content\Contracts\Repositories\NewsRepositoryInterface;
use Modules\Content\Contracts\Services\NewsServiceInterface;
use Modules\Content\DTOs\CreateNewsDTO;
use Modules\Content\DTOs\UpdateNewsDTO;
use Modules\Content\Events\NewsPublished;
use Modules\Content\Models\News;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class NewsService implements NewsServiceInterface
{
    public function __construct(
        private NewsRepositoryInterface $repository
    ) {}

    public function getFeed(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $searchQuery = request('filter.search') ?? request('search');

        $builder = QueryBuilder::for(News::class);

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = News::search($searchQuery)->keys()->toArray();

            if (! empty($ids)) {
                $builder->whereIn('id', $ids);
            } else {
                $builder->whereRaw('1 = 0');
            }
        }

        $builder->allowedFilters([
            AllowedFilter::exact('status'),
            AllowedFilter::exact('category'),
        ])
            ->allowedIncludes(['author'])
            ->allowedSorts(['published_at', 'views_count', 'created_at'])
            ->defaultSort('-published_at');

        return $builder->paginate($perPage);
    }

    public function search(string $query, array $filters = []): LengthAwarePaginator
    {
        request()->merge(['filter' => array_merge(request('filter', []), ['search' => $query])]);

        return $this->getFeed($filters);
    }

    public function find(int $id): ?News
    {
        return $this->repository->findWithRelations($id);
    }

    public function findBySlug(string $slug): ?News
    {
        return $this->repository->findBySlugWithRelations($slug);
    }

    public function create(CreateNewsDTO $dto, User $author): News
    {
        return DB::transaction(function () use ($dto, $author) {
            $data = array_merge($dto->toArray(), [
                'author_id' => $author->id,
            ]);

            return $this->repository->create($data);
        });
    }

    public function update(News $news, UpdateNewsDTO $dto, User $editor): News
    {
        return DB::transaction(function () use ($news, $dto, $editor) {
            $news->saveRevision($editor);

            return $this->repository->update($news, $dto->toArrayWithoutNull());
        });
    }

    public function delete(News $news, User $user): bool
    {
        return $this->repository->delete($news, $user->id);
    }

    /**
     * @throws BusinessException
     */
    public function publish(News $news): News
    {
        if ($news->status === 'published') {
            throw new BusinessException(__('messages.news.already_published'));
        }

        return DB::transaction(function () use ($news) {
            $this->repository->update($news, [
                'status' => 'published',
                'published_at' => now(),
                'scheduled_at' => null,
            ]);

            event(new NewsPublished($news->fresh()));

            return $news->fresh();
        });
    }

    /**
     * @throws BusinessException
     */
    public function schedule(News $news, \Carbon\Carbon $publishAt): News
    {
        if ($publishAt->isPast()) {
            throw new BusinessException(__('messages.content.schedule_future_required'));
        }

        $this->repository->update($news, [
            'status' => 'scheduled',
            'scheduled_at' => $publishAt,
        ]);

        return $news->fresh();
    }

    public function getTrending(int $limit = 10): Collection
    {
        return $this->repository->getTrendingNews($limit);
    }

    public function getFeatured(int $limit = 5): Collection
    {
        return $this->repository->getFeaturedNews($limit);
    }

    public function getScheduledForPublishing(): Collection
    {
        return $this->repository->getScheduledForPublishing();
    }

    public function incrementViews(News $news): void
    {
        $news->increment('views_count');
    }
}
