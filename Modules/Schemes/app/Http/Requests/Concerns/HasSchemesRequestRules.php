<?php

namespace Modules\Schemes\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait HasSchemesRequestRules
{
    protected function rulesCourse(int $courseId = 0): array
    {
        $uniqueCode = Rule::unique('courses', 'code')->whereNull('deleted_at');
        $uniqueSlug = Rule::unique('courses', 'slug')->whereNull('deleted_at');
        if ($courseId > 0) {
            $uniqueCode = $uniqueCode->ignore($courseId);
            $uniqueSlug = $uniqueSlug->ignore($courseId);
        }

        return [
            'code' => ['required', 'string', 'max:50', $uniqueCode],
            'slug' => ['nullable', 'string', 'max:100', $uniqueSlug],
            'title' => ['required', 'string', 'max:255'],
            'short_desc' => ['nullable', 'string'],
            'level_tag' => ['required', Rule::in(['dasar', 'menengah', 'mahir'])],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'progression_mode' => ['required', Rule::in(['sequential', 'free'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'type' => ['sometimes', Rule::in(['okupasi', 'kluster'])],
        ];
    }

    protected function messagesCourse(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode sudah digunakan.',
            'title.required' => 'Judul wajib diisi.',
            'level_tag.required' => 'Level wajib diisi.',
            'visibility.required' => 'Visibility wajib diisi.',
            'progression_mode.required' => 'Mode progres wajib diisi.',
            'category_id.exists' => 'Kategori tidak ditemukan.',
            'status.in' => 'Status tidak valid.',
            'type.in' => 'Tipe tidak valid.',
        ];
    }
}
