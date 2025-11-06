<?php

namespace Modules\Schemes\Services;

use App\Services\UploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Schemes\Jobs\ProcessLessonBlockMedia;
use Modules\Schemes\Models\LessonBlock;
use Illuminate\Support\Str;

class LessonBlockService
{
    public function __construct(private UploadService $uploader) {}

    public function list(int $lessonId)
    {
        return LessonBlock::where('lesson_id', $lessonId)
            ->orderBy('order')
            ->get();
    }

    public function create(int $lessonId, array $data, ?UploadedFile $mediaFile): LessonBlock
    {
        return DB::transaction(function () use ($lessonId, $data, $mediaFile) {
            $nextOrder = LessonBlock::where('lesson_id', $lessonId)->max('order');
            $nextOrder = $nextOrder ? $nextOrder + 1 : 1;

            $path = null;
            if ($mediaFile) {
                $dir = 'lesson_blocks/'.$lessonId;
                $path = $this->uploader->storePublic($mediaFile, $dir);
            }

            $block = LessonBlock::create([
                'lesson_id' => $lessonId,
                'slug' => (string) Str::uuid(),
                'block_type' => $data['type'],
                'content' => $data['content'] ?? null,
                'media_url' => $path,
                'order' => $data['order'] ?? $nextOrder,
            ]);

            if ($path && in_array($data['type'], ['image', 'video', 'file'])) {
                ProcessLessonBlockMedia::dispatch($block->id);
            }

            return $block->fresh();
        });
    }

    public function update(int $lessonId, int $blockId, array $data, ?UploadedFile $mediaFile): LessonBlock
    {
        return DB::transaction(function () use ($lessonId, $blockId, $data, $mediaFile) {
            $block = LessonBlock::where('lesson_id', $lessonId)->findOrFail($blockId);

            $update = [
                'block_type' => $data['type'] ?? $block->block_type,
                'content' => array_key_exists('content', $data) ? $data['content'] : $block->content,
            ];

            if (array_key_exists('order', $data)) {
                $update['order'] = $data['order'];
            }

            if ($mediaFile) {
                if ($block->getRawOriginal('media_url')) {
                    $this->uploader->deletePublic($block->getRawOriginal('media_url'));
                }
                $dir = 'lesson_blocks/'.$lessonId;
                $path = $this->uploader->storePublic($mediaFile, $dir);
                $update['media_url'] = $path;
            }

            $block->update($update);

            if ($mediaFile && in_array($block->block_type, ['image', 'video', 'file'])) {
                ProcessLessonBlockMedia::dispatch($block->id);
            }

            return $block->fresh();
        });
    }

    public function delete(int $lessonId, int $blockId): bool
    {
        $block = LessonBlock::where('lesson_id', $lessonId)->findOrFail($blockId);
        if ($block->getRawOriginal('media_url')) {
            $this->uploader->deletePublic($block->getRawOriginal('media_url'));
        }
        if ($block->getRawOriginal('media_thumbnail_url')) {
            $this->uploader->deletePublic($block->getRawOriginal('media_thumbnail_url'));
        }

        return (bool) $block->delete();
    }
}
