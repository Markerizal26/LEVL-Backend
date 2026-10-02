<?php

declare(strict_types=1);

namespace Modules\Schemes\Http\Requests;

use App\Support\ApiResponse;
use App\Support\ValidationRules\UploadSizeRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Schemes\Enums\BlockType;

class LessonBlockRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'type' => [$isUpdate ? 'sometimes' : 'required', BlockType::rule()],
            'content' => 'nullable|string',
            'order' => 'nullable|integer|min:1',
            'video_source' => 'nullable|in:upload,embed',
            'external_url' => [
                'nullable',
                'url',
                'max:500',
                function ($attribute, $value, $fail) {
                    $resolvedType = $this->resolveBlockType();
                    if (! $resolvedType) {
                        return;
                    }

                    $blockType = BlockType::tryFrom($resolvedType);
                    if (! $blockType) {
                        return;
                    }

                    if ($resolvedType === 'video') {
                        $videoSource = $this->resolveVideoSource();
                        $existingExternalUrl = $this->route('block')?->external_url;
                        if ($videoSource === 'embed' && ! $value && ! $existingExternalUrl) {
                            $fail(__('validation.custom.external_url.required_for_video_embed'));
                        }
                        return;
                    }

                    if ($blockType->requiresExternalUrl() && ! $value) {
                        $fail(__('validation.custom.external_url.required_for_type'));
                    }
                },
            ],
            'media' => [
                'nullable',
                'file',
                new UploadSizeRule(),
                function ($attribute, $value, $fail) use ($isUpdate) {
                    $resolvedType = $this->resolveBlockType();
                    if (! $resolvedType) {
                        return;
                    }

                    $existingMedia = $this->route('block')?->media;

                    if ($resolvedType === 'image' && ! $value && ! $existingMedia) {
                        $fail(__('validation.custom.media.required_for_image'));
                        return;
                    }

                    if ($resolvedType === 'video' && $this->resolveVideoSource() === 'upload' && ! $value && ! $existingMedia) {
                        $fail(__('validation.custom.media.required_for_video_upload'));
                        return;
                    }

                    if ($isUpdate) {
                        return;
                    }

                    $blockType = BlockType::tryFrom($resolvedType);
                    if ($blockType && $blockType->requiresMedia() && ! $value && ! $this->input('external_url')) {
                        $fail(__('validation.custom.media.required_for_type'));
                    }
                },
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }

                    $resolvedType = $this->resolveBlockType();
                    if (! $resolvedType) {
                        return;
                    }

                    $mime = $value->getMimeType();
                    $ok = true;
                    if ($resolvedType === 'image') {
                        $ok = str_starts_with($mime, 'image/');
                    } elseif ($resolvedType === 'video') {
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
            'content.string' => __('validation.string', ['attribute' => __('validation.attributes.content')]),
            'order.integer' => __('validation.integer', ['attribute' => __('validation.attributes.order')]),
            'order.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.order'), 'min' => 1]),
            'video_source.in' => __('validation.in', ['attribute' => __('validation.attributes.video_source')]),
            'external_url.url' => __('validation.url', ['attribute' => __('validation.attributes.external_url')]),
            'external_url.max' => __('validation.max.string', ['attribute' => __('validation.attributes.external_url'), 'max' => 500]),
            'media.file' => __('validation.file', ['attribute' => __('validation.attributes.media')]),
        ];
    }

    private function resolveBlockType(): ?string
    {
        $type = $this->input('type');
        if (is_string($type) && $type !== '') {
            return $type;
        }

        $routeBlockType = $this->route('block')?->block_type;
        if ($routeBlockType instanceof BlockType) {
            return $routeBlockType->value;
        }

        if (is_string($routeBlockType) && $routeBlockType !== '') {
            return $routeBlockType;
        }

        return null;
    }

    private function resolveVideoSource(): string
    {
        $videoSource = $this->input('video_source');
        if (in_array($videoSource, ['upload', 'embed'], true)) {
            return $videoSource;
        }

        $externalUrl = $this->input('external_url');
        if (is_string($externalUrl) && $externalUrl !== '') {
            return 'embed';
        }

        $existingExternalUrl = $this->route('block')?->external_url;
        if (is_string($existingExternalUrl) && $existingExternalUrl !== '') {
            return 'embed';
        }

        return 'upload';
    }
}
