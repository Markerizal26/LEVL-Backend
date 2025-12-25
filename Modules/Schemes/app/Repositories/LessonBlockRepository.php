<?php

namespace Modules\Schemes\Repositories;

use App\Repositories\BaseRepository;
use Modules\Schemes\Contracts\Repositories\LessonBlockRepositoryInterface;
use Modules\Schemes\Models\LessonBlock;

class LessonBlockRepository extends BaseRepository implements LessonBlockRepositoryInterface
{
    protected function model(): string
    {
        return LessonBlock::class;
    }

    public function getMaxOrderForLesson(int $lessonId): int
    {
        $maxOrder = $this->query()
            ->where('lesson_id', $lessonId)
            ->max('order');

        return $maxOrder ? (int) $maxOrder : 0;
    }

    public function create(array $data): LessonBlock
    {
        return LessonBlock::create($data);
    }

    public function findByLessonAndId(int $lessonId, int $blockId): ?LessonBlock
    {
        return $this->query()
            ->where('lesson_id', $lessonId)
            ->where('id', $blockId)
            ->first();
    }
}
