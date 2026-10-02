<?php

declare(strict_types=1);

namespace Modules\Forums\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\ValidationRules\UploadSizeRule;
use Modules\Enrollments\Models\Enrollment;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Schemes\Models\Course;

class CreateReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $thread = $this->route('thread');
        if (! $thread instanceof Thread) {
            return false;
        }

        return $this->canAccessCourse($this->user()->id, $thread->course_id);
    }

    public function rules(): array
    {
        $thread = $this->route('thread');
        $threadId = $thread instanceof Thread ? $thread->id : 0;

        return [
            'content' => [
                'required',
                'string',
                'min:1',
                'max:5000',
                function ($attribute, $value, $fail) {
                    $this->validateMentionedUsernames($value, $fail);
                },
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($threadId) {
                    if ($value) {
                        $parent = Reply::find($value);
                        if (! $parent || $parent->thread_id !== $threadId) {
                            $fail('The selected parent reply does not exist in this thread.');
                        } elseif (! $parent->canHaveChildren()) {
                            $fail('This reply has reached the maximum nesting level.');
                        }
                    }
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
            'content.required' => __('validation.required', ['attribute' => __('validation.attributes.content')]),
            'content.min' => __('validation.min.string', ['attribute' => __('validation.attributes.content'), 'min' => 1]),
            'content.max' => __('validation.max.string', ['attribute' => __('validation.attributes.content'), 'max' => 5000]),
            'parent_id.integer' => __('validation.integer', ['attribute' => __('validation.attributes.parent_reply')]),
            'parent_id.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.parent_reply'), 'min' => 1]),
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
        if ($this->user()->hasRole('Superadmin')) {
            return true;
        }

        if ($this->user()->hasRole(['Admin', 'Instructor'])) {
            $course = Course::find($courseId);
            if ($this->user()->hasRole('Instructor') && $course->instructor_id !== $userId) {
                return Enrollment::where('user_id', $userId)->where('course_id', $courseId)->exists();
            }

            return true;
        }

        return Enrollment::where('user_id', $userId)->where('course_id', $courseId)->exists();
    }
}
