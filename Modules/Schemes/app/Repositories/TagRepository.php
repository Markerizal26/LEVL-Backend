<?php

namespace Modules\Schemes\Repositories;

use App\Repositories\BaseRepository;
use Modules\Schemes\Contracts\Repositories\TagRepositoryInterface;
use Modules\Schemes\Models\Tag;

class TagRepository extends BaseRepository implements TagRepositoryInterface
{
    protected function model(): string
    {
        return Tag::class;
    }

    public function findById(int $id): ?Tag
    {
        return $this->query()->find($id);
    }

    public function create(array $data): Tag
    {
        return Tag::create($data);
    }
}
