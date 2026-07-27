<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveDraftGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        // Normalize top-level is_ai_assisted
        if ($this->has('is_ai_assisted')) {
            $data['is_ai_assisted'] = filter_var($this->input('is_ai_assisted'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        // Normalize is_ai_assisted inside each grade entry
        if ($this->has('grades') && is_array($this->input('grades'))) {
            $grades = $this->input('grades');
            foreach ($grades as $index => $grade) {
                if (array_key_exists('is_ai_assisted', $grade)) {
                    $grades[$index]['is_ai_assisted'] = filter_var($grade['is_ai_assisted'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }
            }
            $data['grades'] = $grades;
        }

        if (! empty($data)) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'grades' => ['nullable', 'array', 'min:1'],
            'grades.*.question_id' => ['required_with:grades', 'integer', 'exists:quiz_questions,id'],
            'grades.*.score' => ['required_with:grades', 'numeric', 'min:0'],
            'grades.*.feedback' => ['nullable', 'string'],
            'grades.*.is_ai_assisted' => ['nullable', 'boolean'],
            'grades.*.ai_suggested_score' => ['nullable', 'numeric', 'min:0'],
            'grades.*.ai_reasoning' => ['nullable', 'string'],
            'grades.*.ai_scoring_reference' => ['nullable', 'string'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'string'],
            'is_ai_assisted' => ['nullable', 'boolean'],
            'ai_suggested_score' => ['nullable', 'numeric', 'min:0'],
            'ai_reasoning' => ['nullable', 'string'],
            'ai_scoring_reference' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasGrades = ! empty($this->input('grades'));
            $hasScore = $this->has('score') && $this->input('score') !== null;

            if (! $hasGrades && ! $hasScore) {
                $validator->errors()->add('grades', 'Either grades array (for quiz) or score (for assignment) must be provided.');
            }

            if ($hasGrades && $hasScore) {
                $validator->errors()->add('grades', 'Cannot provide both grades array and score. Use grades for quiz, score for assignment.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'grades' => __('validation.attributes.grades'),
            'grades.*.question_id' => __('validation.attributes.question_id'),
            'grades.*.score' => __('validation.attributes.score'),
            'grades.*.feedback' => __('validation.attributes.feedback'),
            'score' => __('validation.attributes.score'),
            'feedback' => __('validation.attributes.overall_feedback'),
        ];
    }
}
