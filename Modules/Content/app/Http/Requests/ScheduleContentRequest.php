<?php

namespace Modules\Content\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => 'required|date|after:now',
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.required' => __('validation.required', ['attribute' => __('validation.attributes.scheduled_at')]),
            'scheduled_at.after' => __('validation.after', ['attribute' => __('validation.attributes.scheduled_at'), 'date' => 'now']),
        ];
    }
}
