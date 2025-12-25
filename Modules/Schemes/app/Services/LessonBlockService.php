<?php

namespace Modules\Schemes\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Schemes\Contracts\Repositories\LessonBlockRepositoryInterface;
use Modules\Schemes\Models\LessonBlock;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LessonBlockService
{
    public function __construct(
        private LessonBlockRepositoryInterface $repository
    ) {}

    public function list(int $lessonId): Collection
    {
        $query = QueryBuilder::for(LessonBlock::class)
            ->where('lesson_id', $lessonId)
            ->allowedFilters([
                AllowedFilter::exact('block_type'),
            ])
            ->allowedSorts(['order', 'created_at'])
            ->defaultSort('order');

        return $query->get();
    }

    public function create(int $lessonId, array $data, ?UploadedFile $mediaFile): LessonBlock
    {
        return DB::transaction(function () use ($lessonId, $data, $mediaFile) {
            $nextOrder = $this->repository->getMaxOrderForLesson($lessonId);
            $nextOrder = $nextOrder ? $nextOrder + 1 : 1;

            $block = $this->repository->create([
                'lesson_id' => $lessonId,
                'slug' => (string) Str::uuid(),
                'block_type' => $data['type'],
                'content' => $data['content'] ?? null,
                'order' => $data['order'] ?? $nextOrder,
            ]);

            if ($mediaFile && collect(['image', 'video', 'file'])->contains($data['type'])) {
                $media = $block
                    ->addMedia($mediaFile)
                    ->toMediaCollection('media');

                if ($data['type'] === 'video') {
                    $this->storeVideoMetadata($media);
                }
            }

            return $block->fresh();
        });
    }

    public function update(int $lessonId, int $blockId, array $data, ?UploadedFile $mediaFile): LessonBlock
    {
        return DB::transaction(function () use ($lessonId, $blockId, $data, $mediaFile) {
            $block = $this->repository->findByLessonAndId($lessonId, $blockId);
            
            if (!$block) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            $update = [
                'block_type' => $data['type'] ?? $block->block_type,
                'content' => data_get($data, 'content', $block->content),
            ];

            if (Arr::has($data, 'order')) {
                $update['order'] = $data['order'];
            }

            $block->update($update);

            if ($mediaFile) {
                $block->clearMediaCollection('media');

                $media = $block
                    ->addMedia($mediaFile)
                    ->toMediaCollection('media');

                $blockType = $data['type'] ?? $block->block_type;
                if ($blockType === 'video') {
                    $this->storeVideoMetadata($media);
                }
            }

            return $block->fresh();
        });
    }

    public function delete(int $lessonId, int $blockId): bool
    {
        $block = $this->repository->findByLessonAndId($lessonId, $blockId);
        
        if (!$block) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        return (bool) $block->delete();
    }

    private function storeVideoMetadata($media): void
    {
        try {
            $path = $media->getPath();
            $ffprobe = config('media-library.ffprobe_path', '/usr/bin/ffprobe');

            if (file_exists($path) && is_executable($ffprobe)) {
                $cmd = sprintf(
                    '%s -v quiet -print_format json -show_format -show_streams %s',
                    escapeshellarg($ffprobe),
                    escapeshellarg($path)
                );

                $output = shell_exec($cmd);
                if ($output) {
                    $data = json_decode($output, true);
                    if (isset($data['format']['duration'])) {
                        $media->setCustomProperty('duration', (float) $data['format']['duration']);
                    }
                    if (isset($data['streams'][0])) {
                        $stream = $data['streams'][0];
                        if (isset($stream['width'])) {
                            $media->setCustomProperty('width', (int) $stream['width']);
                        }
                        if (isset($stream['height'])) {
                            $media->setCustomProperty('height', (int) $stream['height']);
                        }
                    }
                    $media->save();
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to extract video metadata: '.$e->getMessage());
        }
    }
}
