<?php

namespace Modules\Schemes\Contracts\Repositories;

use Modules\Schemes\Models\LessonBlock;

interface LessonBlockRepositoryInterface
{
    /**
     * Get maximum order value for a lesson's blocks.
     */
    public function getMaxOrderForLesson(int $lessonId): int;

    /**
     * Create a new lesson block.
     */
    public function create(array $data): LessonBlock;

    /**
     * Find lesson block by lesson ID and block ID.
     */
    public function findByLessonAndId(int $lessonId, int $blockId): ?LessonBlock;
}
