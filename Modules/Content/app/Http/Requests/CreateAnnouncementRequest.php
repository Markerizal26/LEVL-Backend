<?php

namespace Modules\Content\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Content\Enums\ContentStatus;
use Modules\Content\Enums\Priority;
use Modules\Content\Enums\TargetType;

class CreateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'course_id' => 'nullable|exists:courses,id',
            'target_type' => ['required', Rule::enum(TargetType::class)],
            'target_value' => 'nullable|string|max:255',
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'status' => ['nullable', Rule::enum(ContentStatus::class)->only([ContentStatus::Draft, ContentStatus::Published, ContentStatus::Scheduled])],
            'scheduled_at' => 'nullable|date|after:now',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('validation.required', ['attribute' => __('validation.attributes.title')]),
            'content.required' => __('validation.required', ['attribute' => __('validation.attributes.content')]),
            'target_type.required' => __('validation.required', ['attribute' => __('validation.attributes.target_type')]),
            'target_type.in' => __('validation.in', ['attribute' => __('validation.attributes.target_type')]),
            'scheduled_at.after' => __('validation.after', ['attribute' => __('validation.attributes.scheduled_at'), 'date' => 'now']),
        ];
    }
}
