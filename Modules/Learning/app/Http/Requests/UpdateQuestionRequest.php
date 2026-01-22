<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Learning\Enums\QuestionType;

/**
 * Request validation for updating a question.
 */
class UpdateQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(QuestionType::class)],
            'content' => ['sometimes', 'string', 'max:65535'],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:1000'],
            'answer_key' => ['nullable', 'array'],
            'weight' => ['sometimes', 'numeric', 'gt:0'],
            'order' => ['nullable', 'integer', 'min:0'],
            'max_score' => ['nullable', 'numeric', 'gt:0'],
            'max_file_size' => ['nullable', 'integer', 'min:1', 'max:104857600'], // Max 100MB
            'allowed_file_types' => ['nullable', 'array'],
            'allowed_file_types.*' => ['string', 'max:50'],
            'allow_multiple_files' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => __('validation.attributes.question_type'),
            'content' => __('validation.attributes.question_content'),
            'options' => __('validation.attributes.options'),
            'answer_key' => __('validation.attributes.answer_key'),
            'weight' => __('validation.attributes.weight'),
            'order' => __('validation.attributes.order'),
            'max_score' => __('validation.attributes.max_score'),
            'max_file_size' => __('validation.attributes.max_file_size'),
            'allowed_file_types' => __('validation.attributes.allowed_file_types'),
            'allow_multiple_files' => __('validation.attributes.allow_multiple_files'),
        ];
    }
}
