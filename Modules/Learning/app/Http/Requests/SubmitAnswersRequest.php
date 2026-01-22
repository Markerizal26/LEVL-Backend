<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'answers.*.content' => ['nullable', 'string'],
            'answers.*.selected_options' => ['nullable', 'array'],
            'answers.*.selected_options.*' => ['string'],
            'answers.*.file_paths' => ['nullable', 'array'],
            'answers.*.file_paths.*' => ['string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'answers' => __('validation.attributes.answers'),
            'answers.*.question_id' => __('validation.attributes.question_id'),
            'answers.*.content' => __('validation.attributes.content'),
            'answers.*.selected_options' => __('validation.attributes.selected_options'),
            'answers.*.file_paths' => __('validation.attributes.file_paths'),
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required' => __('messages.validations.answers_required'),
            'answers.array' => __('messages.validations.answers_array'),
            'answers.*.question_id.required' => __('messages.validations.question_id_required'),
            'answers.*.question_id.exists' => __('messages.validations.question_not_found'),
        ];
    }
}
