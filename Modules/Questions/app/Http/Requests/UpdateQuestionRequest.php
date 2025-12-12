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
            'category_id.exists' => 'The selected category does not exist.',
            'type.in' => 'The type must be one of: '.implode(', ', QuestionType::values()).'.',
            'difficulty.in' => 'The difficulty must be one of: '.implode(', ', QuestionDifficulty::values()).'.',
            'status.in' => 'The status must be one of: '.implode(', ', QuestionStatus::values()).'.',
            'question_text.max' => 'The question text may not be greater than 5000 characters.',
            'points.min' => 'The points must be at least 1.',
            'options.*.option_text.required_with' => 'Option text is required.',
            'options.*.is_correct.required_with' => 'You must specify if the option is correct.',
        ];
    }
}
