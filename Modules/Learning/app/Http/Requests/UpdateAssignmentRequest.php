<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\SubmissionType;

class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'submission_type' => ['sometimes', Rule::enum(SubmissionType::class)],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'available_from' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:available_from'],
            'status' => ['sometimes', Rule::enum(AssignmentStatus::class)],
            'allow_resubmit' => ['nullable', 'boolean'],
            'late_penalty_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('validation.attributes.title'),
            'description' => __('validation.attributes.description'),
            'submission_type' => __('validation.attributes.submission_type'),
            'max_score' => __('validation.attributes.max_score'),
            'available_from' => __('validation.attributes.available_from'),
            'deadline_at' => __('validation.attributes.deadline_at'),
            'status' => __('validation.attributes.status'),
            'allow_resubmit' => __('validation.attributes.allow_resubmit'),
            'late_penalty_percent' => __('validation.attributes.late_penalty_percent'),
        ];
    }
}
