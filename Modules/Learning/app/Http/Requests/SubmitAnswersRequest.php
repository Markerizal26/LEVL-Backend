<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for submitting answers to a submission.
 *
 * Requirements: 6.3, 6.4
 */
class SubmitAnswersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
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

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.required' => 'You must provide answers to submit.',
            'answers.array' => 'Answers must be provided as an array.',
            'answers.*.question_id.required' => 'Each answer must specify a question ID.',
            'answers.*.question_id.exists' => 'The specified question does not exist.',
        ];
    }
}
