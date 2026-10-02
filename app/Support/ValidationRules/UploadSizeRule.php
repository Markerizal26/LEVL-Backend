<?php

declare(strict_types=1);

namespace App\Support\ValidationRules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

final class UploadSizeRule implements ValidationRule
{
    public const MAX_FILE_SIZE_KB = 2048;

    public const MAX_VIDEO_SIZE_KB = 51200;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $isVideo = str_starts_with((string) $value->getMimeType(), 'video/');
        $maxSizeKb = $isVideo
            ? (int) config('uploads.max_video_size_kb', self::MAX_VIDEO_SIZE_KB)
            : (int) config('uploads.max_file_size_kb', self::MAX_FILE_SIZE_KB);

        if ($value->getSize() > ($maxSizeKb * 1024)) {
            $maxSize = $isVideo ? '50 MB' : '2 MB';

            $fail("The {$attribute} may not be larger than {$maxSize}.");
        }
    }
}
