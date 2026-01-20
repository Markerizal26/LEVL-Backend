<?php

namespace Modules\Questions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Questions\Enums\QuestionDifficulty;
use Modules\Questions\Enums\QuestionStatus;
use Modules\Questions\Enums\QuestionType;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['sometimes', 'string', 'in:'.implode(',', QuestionType::values())],
            'difficulty' => ['sometimes', 'string', 'in:'.implode(',', QuestionDifficulty::values())],
            'question_text' => ['sometimes', 'string', 'max:5000'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'points' => ['sometimes', 'integer', 'min:1'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'meta' => ['nullable', 'array'],
            'status' => ['sometimes', 'string', 'in:'.implode(',', QuestionStatus::values())],
            'options' => ['nullable', 'array'],
            'options.*.option_key' => ['required_with:options', 'string', 'max:10'],
            'options.*.option_text' => ['required_with:options', 'string', 'max:1000'],
            'options.*.is_correct' => ['required_with:options', 'boolean'],
            'options.*.order' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.category')]),
            'type.in' => __('validation.in', ['attribute' => __('validation.attributes.type')]),
            'difficulty.in' => __('validation.in', ['attribute' => __('validation.attributes.difficulty')]),
            'status.in' => __('validation.in', ['attribute' => __('validation.attributes.status')]),
            'question_text.max' => __('validation.max.string', ['attribute' => __('validation.attributes.question_text'), 'max' => 5000]),
            'points.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.points'), 'min' => 1]),
            'options.*.option_text.required_with' => __('validation.required_with', ['attribute' => __('validation.attributes.option_text'), 'values' => __('validation.attributes.options')]),
            'options.*.is_correct.required_with' => __('validation.required_with', ['attribute' => __('validation.attributes.is_correct'), 'values' => __('validation.attributes.options')]),
        ];
    }
}
