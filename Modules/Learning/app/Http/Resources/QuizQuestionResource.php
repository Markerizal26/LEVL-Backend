<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuizQuestionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'content' => $this->content,
            'options' => is_array($this->options)
                ? array_map(static fn ($opt) => is_array($opt) && array_key_exists('text', $opt) ? $opt['text'] : $opt, $this->options)
                : null,
            'answer_key' => $this->when(
                $this->userCanSeeAnswerKey($request),
                fn () => $this->answer_key
            ),
            'weight' => $this->weight,
            'order' => $this->order,
            'max_score' => $this->max_score,
            'can_auto_grade' => $this->canAutoGrade(),
            'requires_options' => $this->requiresOptions(),
            'option_images' => $this->when(
                $this->relationLoaded('media'),
                fn () => $this->getMedia('option_images')->map(fn ($m) => ['id' => $m->id, 'url' => $m->getUrl()])
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function userCanSeeAnswerKey($request): bool
    {
        $user = auth('api')->user();
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Superadmin', 'Admin', 'Instructor']);
    }
}
