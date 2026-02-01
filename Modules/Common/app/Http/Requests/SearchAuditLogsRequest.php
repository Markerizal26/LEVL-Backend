<?php

declare(strict_types=1);

namespace Modules\Common\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Services\AssessmentAuditService;

/**
 * Request validation for audit log search and filtering.
 *
 * Requirement: 20.7
 */
class SearchAuditLogsRequest extends FormRequest
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
        $validActions = [
            AssessmentAuditService::ACTION_SUBMISSION_CREATED,
            AssessmentAuditService::ACTION_STATE_TRANSITION,
            AssessmentAuditService::ACTION_GRADING,
            AssessmentAuditService::ACTION_ANSWER_KEY_CHANGE,
            AssessmentAuditService::ACTION_GRADE_OVERRIDE,

            AssessmentAuditService::ACTION_OVERRIDE_GRANT,
        ];

        return [
            // Filter by single action type
            'action' => ['nullable', 'string', 'in:'.implode(',', $validActions)],

            // Filter by multiple action types
            'actions' => ['nullable', 'array'],
            'actions.*' => ['string', 'in:'.implode(',', $validActions)],

            // Filter by actor
            'actor_id' => ['nullable', 'integer', 'exists:users,id'],
            'actor_type' => ['nullable', 'string'],

            // Filter by subject
            'subject_id' => ['nullable', 'integer'],
            'subject_type' => ['nullable', 'string'],

            // Filter by date range
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],

            // Search in context JSON field
            'context_search' => ['nullable', 'string', 'max:255'],

            // Filter by assignment_id in context
            'assignment_id' => ['nullable', 'integer', 'exists:assignments,id'],

            // Filter by student_id in context
            'student_id' => ['nullable', 'integer', 'exists:users,id'],

            // Pagination
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            'action' => __('validation.attributes.action'),
            'actions' => __('validation.attributes.actions'),
            'actor_id' => __('validation.attributes.actor'),
            'actor_type' => __('validation.attributes.actor_type'),
            'subject_id' => __('validation.attributes.subject'),
            'subject_type' => __('validation.attributes.subject_type'),
            'start_date' => __('validation.attributes.start_date'),
            'end_date' => __('validation.attributes.end_date'),
            'context_search' => __('validation.attributes.search'),
            'assignment_id' => __('validation.attributes.assignment'),
            'student_id' => __('validation.attributes.student'),
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
            'action.in' => __('validation.in', ['attribute' => 'action']),
            'actions.*.in' => __('validation.in', ['attribute' => 'action']),
            'start_date.before_or_equal' => __('validation.before_or_equal', [
                'attribute' => 'start date',
                'date' => 'end date',
            ]),
            'end_date.after_or_equal' => __('validation.after_or_equal', [
                'attribute' => 'end date',
                'date' => 'start date',
            ]),
        ];
    }
}
