<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Learning\Models\Question;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Question $question */
        $question = $this->resource;

        return [
            'id' => $question->id,
            'assignment_id' => $question->assignment_id,
            'type' => $question->type?->value,
            'content' => $question->content,
            'options' => $this->shouldShowAnswerKey($request)
                ? $question->options
                : $this->sanitizeOptionsForStudent($question->options),
            'weight' => (float) $question->weight,
            'order' => $question->order,
            'max_score' => $question->max_score ? (float) $question->max_score : null,
            'max_file_size' => $question->max_file_size,
            'allowed_file_types' => $question->allowed_file_types,
            'allow_multiple_files' => $question->allow_multiple_files,
            'can_auto_grade' => $question->canAutoGrade(),
            'created_at' => $question->created_at?->toIso8601String(),
            'updated_at' => $question->updated_at?->toIso8601String(),

            
            'answer_key' => $this->when(
                $this->shouldShowAnswerKey($request),
                $question->answer_key
            ),
        ];
    }

    private function shouldShowAnswerKey(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Admin', 'Instructor', 'Superadmin']);
    }

    private function sanitizeOptionsForStudent(?array $options): ?array
    {
        if (! $options) {
            return null;
        }

        return array_map(function ($option) {
            if (is_array($option)) {
                unset($option['is_correct']);
            }

            return $option;
        }, $options);
    }
}
