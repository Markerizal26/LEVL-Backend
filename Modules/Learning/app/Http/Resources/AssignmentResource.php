<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Learning\Models\Assignment;

/**
 * Resource for transforming Assignment models to API responses.
 *
 * @mixin Assignment
 */
class AssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Assignment $assignment */
        $assignment = $this->resource;

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'type' => $assignment->type,
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
            'scope_type' => $assignment->scope_type,
            'is_available' => $assignment->isAvailable(),
            'is_past_deadline' => $assignment->isPastDeadline(),
            'created_at' => $assignment->created_at?->toIso8601String(),
            'updated_at' => $assignment->updated_at?->toIso8601String(),

            // Relationships
            'creator' => $this->whenLoaded('creator', function () use ($assignment) {
                return [
                    'id' => $assignment->creator->id,
                    'name' => $assignment->creator->name,
                    'email' => $assignment->creator->email,
                ];
            }),
            'lesson' => $this->whenLoaded('lesson', function () use ($assignment) {
                return [
                    'id' => $assignment->lesson->id,
                    'title' => $assignment->lesson->title,
                    'slug' => $assignment->lesson->slug,
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
        ];
    }

    /**
     * Create a new resource instance.
     *
     * @param  mixed  ...$parameters
     * @return static
     */
    public static function make(...$parameters)
    {
        $resource = $parameters[0] ?? null;

        if ($resource instanceof Assignment) {
            $resource->loadMissing(['creator:id,name,email', 'lesson:id,title,slug']);
        }

        return parent::make(...$parameters);
    }
}
