<?php

namespace Modules\Schemes\Contracts\Repositories;

use Modules\Schemes\Models\Tag;

interface TagRepositoryInterface
{
    /**
     * Find tag by ID.
     */
    public function findById(int $id): ?Tag;

    /**
     * Create a new tag.
     */
    public function create(array $data): Tag;
}
