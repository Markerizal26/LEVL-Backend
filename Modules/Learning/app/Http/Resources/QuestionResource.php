<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Learning\Models\Question;

/**
 * Resource for transforming Question models to API responses.
 *
 * @mixin Question
 */
class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Question $question */
        $question = $this->resource;

        return [
            'id' => $question->id,
            'assignment_id' => $question->assignment_id,
            'type' => $question->type?->value,
            'content' => $question->content,
            'options' => $question->options,
            'weight' => (float) $question->weight,
            'order' => $question->order,
            'max_score' => $question->max_score ? (float) $question->max_score : null,
            'max_file_size' => $question->max_file_size,
            'allowed_file_types' => $question->allowed_file_types,
            'allow_multiple_files' => $question->allow_multiple_files,
            'can_auto_grade' => $question->canAutoGrade(),
            'created_at' => $question->created_at?->toIso8601String(),
            'updated_at' => $question->updated_at?->toIso8601String(),

            // Only include answer_key for instructors (not students)
            'answer_key' => $this->when(
                $this->shouldShowAnswerKey($request),
                $question->answer_key
            ),
        ];
    }

    /**
     * Determine if the answer key should be shown.
     * Only instructors and admins should see the answer key.
     */
    private function shouldShowAnswerKey(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Admin', 'Instructor', 'Superadmin']);
    }
}
