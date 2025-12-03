<?php

namespace Modules\Assessments\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Modules\Assessments\Enums\ExerciseType;
use Modules\Assessments\Enums\QuestionType;
use Modules\Assessments\Enums\ScopeType;

trait HasAssessmentValidationRules
{
    public function rulesExerciseStore(): array
    {
        return [
            'scope_type' => ['required', Rule::enum(ScopeType::class)],
            'scope_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', Rule::enum(ExerciseType::class)],
            'time_limit_minutes' => 'nullable|integer|min:1',
            'max_score' => 'required|numeric|min:0',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date|after:available_from',
        ];
    }

    public function rulesExerciseUpdate(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'max_score' => 'nullable|numeric|min:0',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date|after_or_equal:available_from',
        ];
    }

    public function rulesQuestionStore(): array
    {
        return [
            'question_text' => 'required|string',
            'type' => ['required', Rule::enum(QuestionType::class)],
            'score_weight' => 'required|numeric|min:0',
            'explanation' => 'nullable|string',
        ];
    }

    public function rulesQuestionUpdate(): array
    {
        return [
            'question_text' => 'nullable|string',
            'type' => ['nullable', Rule::enum(QuestionType::class)],
            'score_weight' => 'nullable|numeric|min:0',
            'explanation' => 'nullable|string',
        ];
    }

    public function rulesOptionStore(): array
    {
        return [
            'option_text' => 'required|string',
            'is_correct' => 'required|boolean',
            'explanation' => 'nullable|string',
        ];
    }

    public function rulesOptionUpdate(): array
    {
        return [
            'option_text' => 'nullable|string',
            'is_correct' => 'nullable|boolean',
            'explanation' => 'nullable|string',
        ];
    }

    public function rulesAttemptSubmitAnswer(): array
    {
        return [
            'question_id' => 'required|integer|exists:questions,id',
            'selected_option_id' => 'nullable|integer|exists:question_options,id',
            'answer_text' => 'nullable|string',
        ];
    }

    public function rulesAttemptComplete(): array
    {
        return [];
    }

    public function rulesFeedback(): array
    {
        return [
            'feedback' => 'nullable|string',
            'score_awarded' => 'required|numeric|min:0',
        ];
    }
}
