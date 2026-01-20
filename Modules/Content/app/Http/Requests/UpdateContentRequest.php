<?php

namespace Modules\Content\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Content\Enums\Priority;
use Modules\Content\Enums\TargetType;

class UpdateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
        ];

        // Add news-specific rules
        if ($this->route('news')) {
            $rules['excerpt'] = 'nullable|string';
            $rules['featured_image'] = 'nullable|image|max:5120';
            $rules['is_featured'] = 'nullable|boolean';
            $rules['category_ids'] = 'nullable|array';
            $rules['category_ids.*'] = 'exists:content_categories,id';
            $rules['tag_ids'] = 'nullable|array';
            $rules['tag_ids.*'] = 'exists:tags,id';
        }

        // Add announcement-specific rules
        if ($this->route('announcement')) {
            $rules['target_type'] = ['sometimes', 'required', Rule::enum(TargetType::class)];
            $rules['target_value'] = 'nullable|string|max:255';
            $rules['priority'] = ['nullable', Rule::enum(Priority::class)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => __('validation.required', ['attribute' => __('validation.attributes.title')]),
            'content.required' => __('validation.required', ['attribute' => __('validation.attributes.content')]),
        ];
    }
}
