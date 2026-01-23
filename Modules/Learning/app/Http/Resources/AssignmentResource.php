<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Learning\Models\Assignment;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $assignment = $this->resource;

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'submission_type' => $assignment->submission_type?->value ?? $assignment->submission_type,
            'max_score' => $assignment->max_score,
            'available_from' => $assignment->available_from?->toIso8601String(),
            'deadline_at' => $assignment->deadline_at?->toIso8601String(),
            'tolerance_minutes' => $assignment->tolerance_minutes,
            'max_attempts' => $assignment->max_attempts,
            'cooldown_minutes' => $assignment->cooldown_minutes,
            'retake_enabled' => $assignment->retake_enabled,
            'review_mode' => $assignment->review_mode?->value ?? $assignment->review_mode,
            'randomization_type' => $assignment->randomization_type?->value ?? $assignment->randomization_type,
            'question_bank_count' => $assignment->question_bank_count,
            'status' => $assignment->status?->value ?? $assignment->status,
            'allow_resubmit' => $assignment->allow_resubmit,
            'late_penalty_percent' => $assignment->late_penalty_percent,
            'scope_type' => class_basename($assignment->assignable_type),
            'assignable_slug' => $assignment->assignable?->slug,
            'lesson_slug' => $this->getLessonSlug(),
            'unit_slug' => $this->getUnitSlug(),
            'course_slug' => $this->getCourseSlug(),
            'is_available' => $assignment->isAvailable(),
            'is_past_deadline' => $assignment->isPastDeadline(),
            'created_at' => $assignment->created_at?->toIso8601String(),
            'updated_at' => $assignment->updated_at?->toIso8601String(),

            
            'creator' => $this->whenLoaded('creator', function () use ($assignment) {
                return [
                    'id' => $assignment->creator->id,
                    'name' => $assignment->creator->name,
                    'email' => $assignment->creator->email,
                ];
            }),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'questions_count' => $this->when(
                $assignment->questions_count !== null,
                $assignment->questions_count
            ),
            'prerequisites' => $this->whenLoaded('prerequisites', function () use ($assignment) {
                return $assignment->prerequisites->map(function ($prereq) {
                    return [
                        'id' => $prereq->id,
                        'title' => $prereq->title,
                    ];
                });
            }),
            'attachments' => $assignment->getMedia('attachments')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                ];
            }),
        ];

    }

    public static function make(...$parameters)
    {
        $resource = $parameters[0] ?? null;

        if ($resource instanceof Assignment) {
            $resource->loadMissing(['creator:id,name,email', 'assignable']);
        }

        return parent::make(...$parameters);
    }

    private function getLessonSlug(): ?string
    {
        $resource = $this->resource;
        $type = class_basename($resource->assignable_type);
        
        return $type === 'Lesson' ? $resource->assignable?->slug : null;
    }

    private function getUnitSlug(): ?string
    {
        $resource = $this->resource;
        $type = class_basename($resource->assignable_type);
        
        if ($type === 'Unit') {
            return $resource->assignable?->slug;
        }
        if ($type === 'Lesson') {
             return $resource->assignable?->unit?->slug; 
        }
        return null;
    }

    private function getCourseSlug(): ?string
    {
        $resource = $this->resource;
        $type = class_basename($resource->assignable_type);
        
        if ($type === 'Course') {
             return $resource->assignable?->slug;
        }
        if ($type === 'Unit') {
             return $resource->assignable?->course?->slug;
        }
        if ($type === 'Lesson') {
             return $resource->assignable?->unit?->course?->slug;
        }
        return null;
    }
}
