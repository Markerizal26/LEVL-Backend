<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\RandomizationType;
use Modules\Learning\Enums\ReviewMode;

/**
 * Request validation for duplicating an assignment.
 *
 * Requirements: 25.1, 25.3
 */
class DuplicateAssignmentRequest extends FormRequest
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
     * All fields are optional - they allow overriding the duplicated assignment's values.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'available_from' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:available_from'],
            'tolerance_minutes' => ['nullable', 'integer', 'min:0'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'cooldown_minutes' => ['nullable', 'integer', 'min:0'],
            'retake_enabled' => ['nullable', 'boolean'],
            'review_mode' => ['nullable', Rule::enum(ReviewMode::class)],
            'randomization_type' => ['nullable', Rule::enum(RandomizationType::class)],
            'question_bank_count' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::enum(AssignmentStatus::class)],
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
            'title' => __('validation.attributes.title'),
            'description' => __('validation.attributes.description'),
            'max_score' => __('validation.attributes.max_score'),
            'available_from' => __('validation.attributes.available_from'),
            'deadline_at' => __('validation.attributes.deadline_at'),
            'tolerance_minutes' => __('validation.attributes.tolerance_minutes'),
            'max_attempts' => __('validation.attributes.max_attempts'),
            'cooldown_minutes' => __('validation.attributes.cooldown_minutes'),
            'retake_enabled' => __('validation.attributes.retake_enabled'),
            'review_mode' => __('validation.attributes.review_mode'),
            'randomization_type' => __('validation.attributes.randomization_type'),
            'question_bank_count' => __('validation.attributes.question_bank_count'),
            'status' => __('validation.attributes.status'),
        ];
    }
}
