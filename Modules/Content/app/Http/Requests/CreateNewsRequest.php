<?php

namespace Modules\Content\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Content\Enums\ContentStatus;

class CreateNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:news,slug',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:5120',
            'is_featured' => 'nullable|boolean',
            'status' => ['nullable', Rule::enum(ContentStatus::class)->only([ContentStatus::Draft, ContentStatus::Published, ContentStatus::Scheduled])],
            'scheduled_at' => 'nullable|date|after:now',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:content_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('validation.required', ['attribute' => __('validation.attributes.title')]),
            'content.required' => __('validation.required', ['attribute' => __('validation.attributes.content')]),
            'slug.unique' => __('validation.unique', ['attribute' => __('validation.attributes.slug')]),
            'scheduled_at.after' => __('validation.after', ['attribute' => __('validation.attributes.scheduled_at'), 'date' => 'now']),
        ];
    }
}
