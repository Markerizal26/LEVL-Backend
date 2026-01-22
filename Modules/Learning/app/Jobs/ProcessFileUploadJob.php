<?php

declare(strict_types=1);

namespace Modules\Learning\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Learning\Models\Answer;

/**
 * Job to process file uploads for file upload questions.
 *
 * This job handles file validation and storage in the background,
 * ensuring that file processing doesn't block the submission workflow.
 *
 * Requirements: 18.2 - THE System SHALL validate file types against instructor-configured allowed types
 * Requirements: 18.3 - THE System SHALL validate file size against configured maximum
 * Requirements: 28.6 - THE System SHALL process updates in background jobs using Laravel queues
 * Requirements: 28.8 - WHEN handling file uploads, THE System SHALL stream files directly to storage
 */
class ProcessFileUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param  int  $answerId  The ID of the answer to process files for
     * @param  string  $tempFilePath  The temporary file path
     * @param  string  $originalFileName  The original file name
     * @param  array<string>  $allowedTypes  Array of allowed file extensions
     * @param  int  $maxFileSize  Maximum file size in bytes
     */
    public function __construct(
        public int $answerId,
        public string $tempFilePath,
        public string $originalFileName,
        public array $allowedTypes = [],
        public int $maxFileSize = 10485760 // 10MB default
    ) {
        $this->onQueue('file-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $answer = Answer::find($this->answerId);

        if (! $answer) {
            Log::warning('ProcessFileUploadJob: Answer not found', [
                'answer_id' => $this->answerId,
            ]);

            return;
        }

        Log::info('ProcessFileUploadJob: Starting file processing', [
            'answer_id' => $this->answerId,
            'file_name' => $this->originalFileName,
        ]);

        try {
            // Validate file exists
            if (! Storage::exists($this->tempFilePath)) {
                Log::error('ProcessFileUploadJob: Temporary file not found', [
                    'temp_path' => $this->tempFilePath,
                ]);

                return;
            }

            // Validate file type
            $extension = strtolower(pathinfo($this->originalFileName, PATHINFO_EXTENSION));
            if (! empty($this->allowedTypes) && ! in_array($extension, $this->allowedTypes)) {
                Log::warning('ProcessFileUploadJob: Invalid file type', [
                    'extension' => $extension,
                    'allowed_types' => $this->allowedTypes,
                ]);

                // Clean up temp file
                Storage::delete($this->tempFilePath);

                return;
            }

            // Validate file size
            $fileSize = Storage::size($this->tempFilePath);
            if ($fileSize > $this->maxFileSize) {
                Log::warning('ProcessFileUploadJob: File size exceeds limit', [
                    'file_size' => $fileSize,
                    'max_size' => $this->maxFileSize,
                ]);

                // Clean up temp file
                Storage::delete($this->tempFilePath);

                return;
            }

            // Move file to permanent storage
            $permanentPath = $this->generatePermanentPath($answer, $extension);
            Storage::move($this->tempFilePath, $permanentPath);

            // Update answer with file path
            $filePaths = $answer->file_paths ?? [];
            $filePaths[] = [
                'path' => $permanentPath,
                'original_name' => $this->originalFileName,
                'size' => $fileSize,
                'extension' => $extension,
                'uploaded_at' => now()->toIso8601String(),
            ];

            $answer->update(['file_paths' => $filePaths]);

            Log::info('ProcessFileUploadJob: File processing completed', [
                'answer_id' => $this->answerId,
                'permanent_path' => $permanentPath,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessFileUploadJob: Failed to process file', [
                'answer_id' => $this->answerId,
                'error' => $e->getMessage(),
            ]);

            // Clean up temp file on error
            if (Storage::exists($this->tempFilePath)) {
                Storage::delete($this->tempFilePath);
            }

            throw $e;
        }
    }

    /**
     * Generate a permanent storage path for the file.
     */
    private function generatePermanentPath(Answer $answer, string $extension): string
    {
        $submissionId = $answer->submission_id;
        $questionId = $answer->question_id;
        $timestamp = now()->format('YmdHis');
        $uniqueId = uniqid();

        return "submissions/{$submissionId}/answers/{$questionId}/{$timestamp}_{$uniqueId}.{$extension}";
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessFileUploadJob: Job failed after all retries', [
            'answer_id' => $this->answerId,
            'file_name' => $this->originalFileName,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Clean up temp file on failure
        if (Storage::exists($this->tempFilePath)) {
            Storage::delete($this->tempFilePath);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'process-file-upload',
            'answer:'.$this->answerId,
            'file:'.$this->originalFileName,
        ];
    }
}
