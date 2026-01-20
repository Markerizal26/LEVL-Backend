<?php

declare(strict_types=1);

namespace Modules\Schemes\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;

class LessonBlockRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxMb = config('app.lesson_block_max_upload_mb', 50);
        $maxKb = $maxMb * 1024;

        return [
            'type' => 'required|in:text,video,image,file',
            'content' => 'nullable|string',
            'order' => 'nullable|integer|min:1',
            'media' => [
                'nullable',
                'file',
                'max:'.$maxKb,
                function ($attribute, $value, $fail) {
                    $type = $this->input('type');
                    if (in_array($type, ['video', 'image', 'file']) && ! $value) {
                        $fail(__('validation.custom.media.required_for_type'));
                    }
                },
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }
                    $type = $this->input('type');
                    $mime = $value->getMimeType();
                    $ok = true;
                    if ($type === 'image') {
                        $ok = str_starts_with($mime, 'image/');
                    } elseif ($type === 'video') {
                        $ok = str_starts_with($mime, 'video/');
                    }
                    if (! $ok) {
                        $fail(__('validation.custom.media.mismatch_type'));
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => __('validation.required', ['attribute' => __('validation.attributes.type')]),
            'type.in' => __('validation.in', ['attribute' => __('validation.attributes.type')]),
            'content.string' => __('validation.string', ['attribute' => __('validation.attributes.content')]),
            'order.integer' => __('validation.integer', ['attribute' => __('validation.attributes.order')]),
            'order.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.order'), 'min' => 1]),
            'media.file' => __('validation.file', ['attribute' => __('validation.attributes.media')]),
            'media.max' => __('validation.max.file', ['attribute' => __('validation.attributes.media'), 'max' => config('app.lesson_block_max_upload_mb', 50)]),
        ];
    }
}
