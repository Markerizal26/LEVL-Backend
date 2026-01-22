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

class ProcessFileUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $answerId,
        public string $tempFilePath,
        public string $originalFileName,
        public array $allowedTypes = [],
        public int $maxFileSize = 10485760 
    ) {
        $this->onQueue('file-processing');
    }

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
            
            if (! Storage::exists($this->tempFilePath)) {
                Log::error('ProcessFileUploadJob: Temporary file not found', [
                    'temp_path' => $this->tempFilePath,
                ]);

                return;
            }

            
            $extension = strtolower(pathinfo($this->originalFileName, PATHINFO_EXTENSION));
            if (! empty($this->allowedTypes) && ! in_array($extension, $this->allowedTypes)) {
                Log::warning('ProcessFileUploadJob: Invalid file type', [
                    'extension' => $extension,
                    'allowed_types' => $this->allowedTypes,
                ]);

                
                Storage::delete($this->tempFilePath);

                return;
            }

            
            $fileSize = Storage::size($this->tempFilePath);
            if ($fileSize > $this->maxFileSize) {
                Log::warning('ProcessFileUploadJob: File size exceeds limit', [
                    'file_size' => $fileSize,
                    'max_size' => $this->maxFileSize,
                ]);

                
                Storage::delete($this->tempFilePath);

                return;
            }

            
            $permanentPath = $this->generatePermanentPath($answer, $extension);
            Storage::move($this->tempFilePath, $permanentPath);

            
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

            
            if (Storage::exists($this->tempFilePath)) {
                Storage::delete($this->tempFilePath);
            }

            throw $e;
        }
    }

    private function generatePermanentPath(Answer $answer, string $extension): string
    {
        $submissionId = $answer->submission_id;
        $questionId = $answer->question_id;
        $timestamp = now()->format('YmdHis');
        $uniqueId = uniqid();

        return "submissions/{$submissionId}/answers/{$questionId}/{$timestamp}_{$uniqueId}.{$extension}";
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessFileUploadJob: Job failed after all retries', [
            'answer_id' => $this->answerId,
            'file_name' => $this->originalFileName,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        
        if (Storage::exists($this->tempFilePath)) {
            Storage::delete($this->tempFilePath);
        }
    }

    public function tags(): array
    {
        return [
            'process-file-upload',
            'answer:'.$this->answerId,
            'file:'.$this->originalFileName,
        ];
    }
}
