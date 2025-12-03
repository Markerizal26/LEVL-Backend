<?php

namespace Modules\Schemes\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Modules\Common\Http\Requests\Concerns\HasCommonValidationMessages;
use Modules\Schemes\Enums\CourseStatus;
use Modules\Schemes\Enums\CourseType;
use Modules\Schemes\Enums\EnrollmentType;
use Modules\Schemes\Enums\LevelTag;
use Modules\Schemes\Enums\ProgressionMode;

trait HasSchemesRequestRules
{
    use HasCommonValidationMessages;

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
            'level_tag' => ['required', Rule::enum(LevelTag::class)],
            'type' => ['required', Rule::enum(CourseType::class)],
            'enrollment_type' => ['required', Rule::enum(EnrollmentType::class)],
            'enrollment_key' => [
                Rule::requiredIf(function () use ($courseId) {
                    $value = $this->input('enrollment_type');

                    if ($value !== 'key_based') {
                        return false;
                    }

                    if ($courseId > 0) {
                        return false;
                    }

                    return true;
                }),
                'nullable',
                'string',
                'max:100',
            ],
            'progression_mode' => ['required', Rule::enum(ProgressionMode::class)],
            'category_id' => [Rule::requiredIf(fn () => $courseId === 0), 'integer', 'exists:categories,id'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string'],
            'outcomes' => ['sometimes', 'array'],
            'outcomes.*' => ['string'],
            'prereq' => ['sometimes', 'nullable', 'string'],
            'thumbnail' => ['sometimes', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'banner' => ['sometimes', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'status' => ['sometimes', Rule::enum(CourseStatus::class)],
            'instructor_id' => ['sometimes', 'integer', 'exists:users,id'],
            'course_admins' => ['sometimes', 'array'],
            'course_admins.*' => ['integer', 'exists:users,id'],
        ];
    }

    protected function messagesCourse(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode sudah digunakan.',
            'title.required' => 'Judul wajib diisi.',
            'level_tag.required' => 'Level wajib diisi.',
            'type.required' => 'Tipe wajib diisi.',
            'enrollment_type.required' => 'Jenis enrollment wajib dipilih.',
            'enrollment_type.in' => 'Jenis enrollment tidak valid.',
            'enrollment_key.required_if' => 'Kode enrollment wajib diisi untuk mode key-based.',
            'enrollment_key.string' => 'Kode enrollment harus berupa teks.',
            'enrollment_key.max' => 'Kode enrollment maksimal 100 karakter.',
            'progression_mode.required' => 'Mode progres wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.integer' => 'Kategori harus berupa ID (angka).',
            'category_id.exists' => 'Kategori tidak ditemukan.',
            'status.in' => 'Status tidak valid.',
            'type.in' => 'Tipe tidak valid.',
            'thumbnail.image' => 'Thumbnail harus berupa gambar.',
            'banner.image' => 'Banner harus berupa gambar.',
            'instructor_id.exists' => 'Instruktur tidak ditemukan.',
            'course_admins.*.exists' => 'Admin course tidak ditemukan.',
            'course_admins.*.integer' => 'Setiap item course_admins harus berupa angka (ID pengguna).',
            'outcomes.*.string' => 'Setiap item outcomes harus berupa teks.',
            'prereq.string' => 'Prasyarat harus berupa teks.',
            'tags.*.string' => 'Setiap item tags harus berupa teks.',
            'tags.array' => 'Field tags harus berupa array.',
            'outcomes.array' => 'Field outcomes harus berupa array.',
            'course_admins.array' => 'Field course_admins harus berupa array.',
        ];
    }

    protected function rulesUnit(int $courseId, int $unitId = 0): array
    {
        $uniqueCode = Rule::unique('units', 'code');
        $uniqueSlug = Rule::unique('units', 'slug')
            ->where('course_id', $courseId);

        if ($unitId > 0) {
            $uniqueCode = $uniqueCode->ignore($unitId);
            $uniqueSlug = $uniqueSlug->ignore($unitId);
        }

        return [
            'code' => ['required', 'string', 'max:50', $uniqueCode],
            'slug' => ['nullable', 'string', 'max:100', $uniqueSlug],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::enum(CourseStatus::class)->only([CourseStatus::Draft, CourseStatus::Published])],
        ];
    }

    protected function messagesUnit(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode sudah digunakan.',
            'slug.unique' => 'Slug sudah digunakan di course ini.',
            'title.required' => 'Judul wajib diisi.',
            'order.integer' => 'Order harus berupa angka.',
            'order.min' => 'Order minimal 1.',
            'status.in' => 'Status harus draft atau published.',
        ];
    }

    protected function rulesReorderUnits(): array
    {
        return [
            'units' => ['required', 'array'],
            'units.*' => ['required', 'integer', 'exists:units,id'],
        ];
    }

    protected function messagesReorderUnits(): array
    {
        return [
            'units.required' => 'Daftar units wajib diisi.',
            'units.array' => 'Units harus berupa array.',
            'units.*.required' => 'Setiap item units wajib diisi.',
            'units.*.integer' => 'Setiap item units harus berupa ID (angka).',
            'units.*.exists' => 'Unit tidak ditemukan.',
        ];
    }

    protected function rulesLesson(int $unitId, int $lessonId = 0): array
    {
        $uniqueSlug = Rule::unique('lessons', 'slug')
            ->where('unit_id', $unitId);

        if ($lessonId > 0) {
            $uniqueSlug = $uniqueSlug->ignore($lessonId);
        }

        return [
            'slug' => ['nullable', 'string', 'max:100', $uniqueSlug],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'markdown_content' => ['nullable', 'string'],
            'order' => ['sometimes', 'integer', 'min:1'],
            'duration_minutes' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::enum(CourseStatus::class)->only([CourseStatus::Draft, CourseStatus::Published])],
        ];
    }

    protected function messagesLesson(): array
    {
        return [
            'slug.unique' => 'Slug sudah digunakan di unit ini.',
            'title.required' => 'Judul wajib diisi.',
            'markdown_content.string' => 'Markdown content harus berupa teks.',
            'order.integer' => 'Order harus berupa angka.',
            'order.min' => 'Order minimal 1.',
            'duration_minutes.integer' => 'Durasi harus berupa angka.',
            'duration_minutes.min' => 'Durasi minimal 0.',
            'status.in' => 'Status harus draft atau published.',
        ];
    }
}
