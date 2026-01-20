<?php

declare(strict_types=1);

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
                        $course = \Modules\Schemes\Models\Course::find($courseId);
                        if ($course && ! empty($course->enrollment_key_hash)) {
                            return false;
                        }
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
            'code.required' => __('validation.required', ['attribute' => __('validation.attributes.code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('validation.attributes.code')]),
            'title.required' => __('validation.required', ['attribute' => __('validation.attributes.title')]),
            'level_tag.required' => __('validation.required', ['attribute' => __('validation.attributes.level')]),
            'type.required' => __('validation.required', ['attribute' => __('validation.attributes.type')]),
            'enrollment_type.required' => __('validation.required', ['attribute' => __('validation.attributes.enrollment_type')]),
            'enrollment_type.in' => __('validation.in', ['attribute' => __('validation.attributes.enrollment_type')]),
            'enrollment_key.required_if' => __('validation.required_if', ['attribute' => __('validation.attributes.enrollment_key'), 'other' => __('validation.attributes.enrollment_type')]),
            'enrollment_key.string' => __('validation.string', ['attribute' => __('validation.attributes.enrollment_key')]),
            'enrollment_key.max' => __('validation.max.string', ['attribute' => __('validation.attributes.enrollment_key'), 'max' => 100]),
            'progression_mode.required' => __('validation.required', ['attribute' => __('validation.attributes.progression_mode')]),
            'category_id.required' => __('validation.required', ['attribute' => __('validation.attributes.category')]),
            'category_id.integer' => __('validation.integer', ['attribute' => __('validation.attributes.category')]),
            'category_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.category')]),
            'status.in' => __('validation.in', ['attribute' => __('validation.attributes.status')]),
            'type.in' => __('validation.in', ['attribute' => __('validation.attributes.type')]),
            'thumbnail.image' => __('validation.image', ['attribute' => __('validation.attributes.thumbnail')]),
            'banner.image' => __('validation.image', ['attribute' => __('validation.attributes.banner')]),
            'instructor_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.instructor')]),
            'course_admins.*.exists' => __('validation.exists', ['attribute' => __('validation.attributes.course_admin')]),
            'course_admins.*.integer' => __('validation.integer', ['attribute' => __('validation.attributes.course_admin')]),
            'outcomes.*.string' => __('validation.string', ['attribute' => __('validation.attributes.outcome')]),
            'prereq.string' => __('validation.string', ['attribute' => __('validation.attributes.prerequisite')]),
            'tags.*.string' => __('validation.string', ['attribute' => __('validation.attributes.tag')]),
            'tags.array' => __('validation.array', ['attribute' => __('validation.attributes.tags')]),
            'outcomes.array' => __('validation.array', ['attribute' => __('validation.attributes.outcomes')]),
            'course_admins.array' => __('validation.array', ['attribute' => __('validation.attributes.course_admins')]),
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
            'code.required' => __('validation.required', ['attribute' => __('validation.attributes.code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('validation.attributes.code')]),
            'slug.unique' => __('validation.unique', ['attribute' => __('validation.attributes.slug')]),
            'title.required' => __('validation.required', ['attribute' => __('validation.attributes.title')]),
            'order.integer' => __('validation.integer', ['attribute' => __('validation.attributes.order')]),
            'order.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.order'), 'min' => 1]),
            'status.in' => __('validation.in', ['attribute' => __('validation.attributes.status')]),
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
            'units.required' => __('validation.required', ['attribute' => __('validation.attributes.units')]),
            'units.array' => __('validation.array', ['attribute' => __('validation.attributes.units')]),
            'units.*.required' => __('validation.required', ['attribute' => __('validation.attributes.unit_item')]),
            'units.*.integer' => __('validation.integer', ['attribute' => __('validation.attributes.unit_item')]),
            'units.*.exists' => __('validation.exists', ['attribute' => __('validation.attributes.unit')]),
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
            'slug.unique' => __('validation.unique', ['attribute' => __('validation.attributes.slug')]),
            'title.required' => __('validation.required', ['attribute' => __('validation.attributes.title')]),
            'markdown_content.string' => __('validation.string', ['attribute' => __('validation.attributes.markdown_content')]),
            'order.integer' => __('validation.integer', ['attribute' => __('validation.attributes.order')]),
            'order.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.order'), 'min' => 1]),
            'duration_minutes.integer' => __('validation.integer', ['attribute' => __('validation.attributes.duration_minutes')]),
            'duration_minutes.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.duration_minutes'), 'min' => 0]),
            'status.in' => __('validation.in', ['attribute' => __('validation.attributes.status')]),
        ];
    }
}
