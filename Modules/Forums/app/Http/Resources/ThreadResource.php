<?php

declare(strict_types=1);

namespace Modules\Forums\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThreadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attachments = $this->whenLoaded('media', function () {
            return $this->getMedia('attachments')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'url' => $media->original_url,
                    'thumb_url' => $media->getUrl('thumb'),
                    'preview_url' => $media->getUrl('preview'),
                ];
            });
        });

        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'author' => $this->whenLoaded('author', function () {
                return [
                    'name' => $this->author?->name,
                    'username' => $this->author?->username,
                    'email' => $this->author?->email,
                    'avatar' => $this->author && $this->author->relationLoaded('media') ? ($this->author->getFirstMediaUrl('avatar') ?: null) : null,
                ];
            }),
            'course' => $this->whenLoaded('course', function () {
                return [
                    'name' => $this->course?->title,
                    'slug' => $this->course?->slug,
                ];
            }),
            'is_pinned' => $this->is_pinned,
            'is_closed' => $this->is_closed,
            'is_resolved' => $this->is_resolved,
            'is_mentioned' => (bool) $this->is_mentioned,
            'views_count' => $this->views_count,
            'replies_count' => $this->replies_count,
            'replies' => ReplyResource::collection($this->whenLoaded('topLevelReplies')),
            'attachments' => $attachments,
            'last_activity_at' => $this->last_activity_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'edited_at' => $this->edited_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
