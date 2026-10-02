<?php

declare(strict_types=1);

namespace Modules\Forums\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\ValidationRules\UploadSizeRule;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

class CreateThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');
        $userId = $this->user()->id;

        if (! $course) {
            return false;
        }

        return $this->canAccessCourse($userId, $course->id);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3|max:255',
            'content' => [
                'required',
                'string',
                'min:1',
                'max:5000',
                function ($attribute, $value, $fail) {
                    $this->validateMentionedUsernames($value, $fail);
                },
            ],
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => [
                'file',
                'mimes:jpeg,png,jpg,gif,pdf,mp4,webm,ogg,mov,avi',
                new UploadSizeRule(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('validation.required', ['attribute' => __('validation.attributes.title')]),
            'title.min' => __('validation.min.string', ['attribute' => __('validation.attributes.title'), 'min' => 3]),
            'title.max' => __('validation.max.string', ['attribute' => __('validation.attributes.title'), 'max' => 255]),
            'content.required' => __('validation.required', ['attribute' => __('validation.attributes.content')]),
            'content.min' => __('validation.min.string', ['attribute' => __('validation.attributes.content'), 'min' => 1]),
            'content.max' => __('validation.max.string', ['attribute' => __('validation.attributes.content'), 'max' => 5000]),
            'forumable_type.required' => __('validation.required', ['attribute' => 'forum type']),
            'forumable_type.in' => __('validation.in', ['attribute' => 'forum type']),
            'forumable_slug.required' => __('validation.required', ['attribute' => 'forum slug']),
            'attachments.array' => __('validation.array', ['attribute' => 'attachments']),
            'attachments.max' => __('validation.max.array', ['attribute' => 'attachments', 'max' => 5]),
            'attachments.*.file' => __('validation.file', ['attribute' => 'attachment']),
            'attachments.*.mimes' => __('validation.mimes', ['attribute' => 'attachment', 'values' => 'jpeg,png,jpg,gif,pdf,mp4,webm,ogg,mov,avi']),
        ];
    }

    private function validateMentionedUsernames(string $content, $fail): void
    {
        preg_match_all('/@([a-zA-Z0-9._-]+)/', $content, $matches);

        if (empty($matches[1])) {
            return;
        }

        $mentionedUsernames = array_unique($matches[1]);
        $existingUsernames = \Modules\Auth\Models\User::whereIn('username', $mentionedUsernames)
            ->pluck('username')
            ->toArray();

        $invalidUsernames = array_diff($mentionedUsernames, $existingUsernames);

        if (! empty($invalidUsernames)) {
            $fail(__('validation.mentioned_users_not_found', [
                'usernames' => implode(', ', array_map(fn ($u) => "@{$u}", $invalidUsernames)),
            ]));
        }
    }

    private function canAccessCourse(int $userId, int $courseId): bool
    {
        if ($this->user()->hasRole(['Admin', 'Superadmin', 'Instructor'])) {
            $course = Course::find($courseId);
            if ($this->user()->hasRole('Instructor') && $course->instructor_id !== $userId) {
                return Enrollment::where('user_id', $userId)->where('course_id', $courseId)->exists();
            }

            return true;
        }

        return Enrollment::where('user_id', $userId)->where('course_id', $courseId)->exists();
    }
}
