<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for Grade model.
 */
class GradeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'user_id' => $this->user_id,
            'graded_by' => $this->graded_by,
            'score' => $this->score,
            'max_score' => $this->max_score,
            'original_score' => $this->original_score,
            'is_override' => $this->is_override,
            'override_reason' => $this->override_reason,
            'feedback' => $this->feedback,
            'is_draft' => $this->is_draft,
            'graded_at' => $this->graded_at,
            'released_at' => $this->released_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'submission' => $this->whenLoaded('submission'),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'grader' => $this->whenLoaded('grader', function () {
                return [
                    'id' => $this->grader->id,
                    'name' => $this->grader->name,
                    'email' => $this->grader->email,
                ];
            }),
        ];
    }
}
