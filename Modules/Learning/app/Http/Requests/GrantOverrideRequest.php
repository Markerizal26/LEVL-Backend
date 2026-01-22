<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Learning\Enums\OverrideType;

class GrantOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::enum(OverrideType::class)],
            'reason' => ['required', 'string', 'min:10', 'max:1000'], 
            'value' => ['nullable', 'array'],
            'value.additional_attempts' => ['nullable', 'integer', 'min:1'],
            'value.extended_deadline' => ['nullable', 'date', 'after:now'],
            'value.bypassed_prerequisites' => ['nullable', 'array'],
            'value.bypassed_prerequisites.*' => ['integer', 'exists:assignments,id'],
            'value.expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $type = $this->input('type');
            $value = $this->input('value', []);

            
            if ($type === OverrideType::Attempts->value) {
                if (empty($value['additional_attempts'])) {
                    $validator->errors()->add(
                        'value.additional_attempts',
                        __('messages.validations.override_attempts_required')
                    );
                }
            }

            if ($type === OverrideType::Deadline->value) {
                if (empty($value['extended_deadline'])) {
                    $validator->errors()->add(
                        'value.extended_deadline',
                        __('messages.validations.override_deadline_required')
                    );
                }
            }
        });
    }

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
