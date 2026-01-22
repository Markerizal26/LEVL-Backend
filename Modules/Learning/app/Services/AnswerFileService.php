<?php

declare(strict_types=1);

namespace Modules\Learning\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Learning\Exceptions\FileExpiredException;
use Modules\Learning\Models\Answer;
use Modules\Learning\Models\Question;

class AnswerFileService
{
    private function getDisk(): string
    {
        return config('filesystems.default', 'do');
    }

    private const BASE_PATH = 'answers';

    private const DEFAULT_MAX_SIZE = 10 * 1024 * 1024; // 10MB

    /**
     * Upload files for an answer.
     *
     * @param  array<UploadedFile>  $files
     * @return array<string> File paths
     *
     * @throws \InvalidArgumentException
     */
    public function uploadFiles(Answer $answer, array $files): array
    {
        $question = $answer->question;
        $this->validateFiles($question, $files);

        $paths = [];
        $metadata = [];

        foreach ($files as $file) {
            $path = $this->storeFile($answer, $file);
            $paths[] = $path;

            // Store metadata for preservation after deletion
            $metadata[] = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'type' => $file->getMimeType(),
                'uploaded_at' => now()->toIso8601String(),
            ];
        }

        // Store file metadata for future reference
        $answer->storeFileMetadata($metadata);

        return $paths;
    }

    /**
     * Delete files for an answer.
     */
    public function deleteFiles(Answer $answer): void
    {
        $filePaths = $answer->file_paths ?? [];

        foreach ($filePaths as $path) {
            Storage::disk($this->getDisk())->delete($path);
        }
    }

    /**
     * Delete expired files for an answer and clear file paths.
     * Preserves file metadata for record keeping.
     */
    public function deleteExpiredFiles(Answer $answer): bool
    {
        if (! $answer->filesExpired()) {
            return false;
        }

        $this->deleteFiles($answer);
        $answer->clearFilePaths();

        return true;
    }

    /**
     * Get a secure download URL for a file.
     *
     * @throws FileExpiredException
     */
    public function getDownloadUrl(Answer $answer, string $path): string
    {
        $this->checkFileNotExpired($answer);

        return Storage::disk($this->getDisk())->url($path);
    }

    /**
     * Check if a file exists.
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk($this->getDisk())->exists($path);
    }

    /**
     * Get file contents.
     *
     * @throws FileExpiredException
     */
    public function getFileContents(Answer $answer, string $path): ?string
    {
        $this->checkFileNotExpired($answer);

        if (! $this->fileExists($path)) {
            return null;
        }

        return Storage::disk($this->getDisk())->get($path);
    }

    /**
     * Check if files are expired and throw exception if so.
     *
     * @throws FileExpiredException
     */
    public function checkFileNotExpired(Answer $answer): void
    {
        if ($answer->filesExpired()) {
            throw new FileExpiredException(
                'The requested file has expired and is no longer available.',
                $answer->getFileMetadata()
            );
        }
    }

    /**
     * Check if files can be accessed (not expired).
     */
    public function canAccessFiles(Answer $answer): bool
    {
        return ! $answer->filesExpired();
    }

    /**
     * Get file metadata for an answer (available even after files are deleted).
     *
     * @return array<array{name: string, size: int, type: string, uploaded_at: string}>|null
     */
    public function getFileMetadata(Answer $answer): ?array
    {
        return $answer->getFileMetadata();
    }

    /**
     * Build file metadata from existing files.
     *
     * @return array<array{name: string, size: int, type: string, uploaded_at: string}>
     */
    public function buildFileMetadata(Answer $answer): array
    {
        $filePaths = $answer->file_paths ?? [];
        $metadata = [];

        foreach ($filePaths as $path) {
            if ($this->fileExists($path)) {
                $metadata[] = [
                    'name' => basename($path),
                    'size' => Storage::disk($this->getDisk())->size($path),
                    'type' => Storage::disk($this->getDisk())->mimeType($path),
                    'uploaded_at' => $answer->created_at?->toIso8601String() ?? now()->toIso8601String(),
                ];
            }
        }

        return $metadata;
    }

    /**
     * Validate files against question configuration.
     *
     * @param  array<UploadedFile>  $files
     *
     * @throws \InvalidArgumentException
     */
    private function validateFiles(Question $question, array $files): void
    {
        // Check multiple files allowed
        if (count($files) > 1 && ! $question->allow_multiple_files) {
            throw new \InvalidArgumentException('Multiple files are not allowed for this question');
        }

        $maxSize = $question->max_file_size ?? self::DEFAULT_MAX_SIZE;
        $allowedTypes = $question->allowed_file_types ?? [];

        foreach ($files as $file) {
            // Validate file size
            if ($file->getSize() > $maxSize) {
                $maxSizeMB = round($maxSize / 1024 / 1024, 2);
                throw new \InvalidArgumentException(
                    "File size exceeds maximum allowed size of {$maxSizeMB}MB"
                );
            }

            // Validate file type
            if (! empty($allowedTypes)) {
                $extension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();

                $isAllowed = in_array($extension, $allowedTypes, true)
                    || in_array($mimeType, $allowedTypes, true);

                if (! $isAllowed) {
                    throw new \InvalidArgumentException(
                        "File type '{$extension}' is not allowed. Allowed types: ".implode(', ', $allowedTypes)
                    );
                }
            }
        }
    }

    /**
     * Store a single file.
     */
    private function storeFile(Answer $answer, UploadedFile $file): string
    {
        $directory = sprintf(
            '%s/%d/%d',
            self::BASE_PATH,
            $answer->submission_id,
            $answer->question_id
        );

        return $file->store($directory, $this->getDisk());
    }
}
