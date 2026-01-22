<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Learning\Enums\OverrideType;

/**
 * Request validation for granting an override to a student.
 *
 * Requirements: 24.1, 24.2, 24.3, 24.4
 */
class GrantOverrideRequest extends FormRequest
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
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::enum(OverrideType::class)],
            'reason' => ['required', 'string', 'min:10', 'max:1000'], // Requirement 24.4
            'value' => ['nullable', 'array'],
            'value.additional_attempts' => ['nullable', 'integer', 'min:1'],
            'value.extended_deadline' => ['nullable', 'date', 'after:now'],
            'value.bypassed_prerequisites' => ['nullable', 'array'],
            'value.bypassed_prerequisites.*' => ['integer', 'exists:assignments,id'],
            'value.expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $type = $this->input('type');
            /** @var array<string, mixed> $value */
            $value = $this->input('value', []);

            // Validate type-specific requirements
            if ($type === OverrideType::Attempts->value) {
                if (empty($value['additional_attempts'])) {
                    $validator->errors()->add(
                        'value.additional_attempts',
                        'Additional attempts is required for attempts override.'
                    );
                }
            }

            if ($type === OverrideType::Deadline->value) {
                if (empty($value['extended_deadline'])) {
                    $validator->errors()->add(
                        'value.extended_deadline',
                        'Extended deadline is required for deadline override.'
                    );
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => __('validation.attributes.student'),
            'type' => __('validation.attributes.override_type'),
            'reason' => __('validation.attributes.reason'),
            'value.additional_attempts' => __('validation.attributes.additional_attempts'),
            'value.extended_deadline' => __('validation.attributes.extended_deadline'),
            'value.bypassed_prerequisites' => __('validation.attributes.bypassed_prerequisites'),
            'value.expires_at' => __('validation.attributes.expires_at'),
        ];
    }
}
