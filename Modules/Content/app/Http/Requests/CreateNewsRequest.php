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
            'featured_image_path' => 'nullable|string|max:255',
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
            'title.required' => 'Judul berita wajib diisi.',
            'content.required' => 'Konten berita wajib diisi.',
            'slug.unique' => 'Slug sudah digunakan.',
            'scheduled_at.after' => 'Waktu publikasi harus di masa depan.',
        ];
    }
}
