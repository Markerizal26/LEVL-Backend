<?php

declare(strict_types=1);

namespace Modules\Learning\Console\Commands;

use Illuminate\Console\Command;
use Modules\Learning\Models\Answer;
use Modules\Learning\Services\AnswerFileService;

class CleanupExpiredFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grading:cleanup-expired-files
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--batch-size= : Number of files to process per batch}
                            {--mark-only : Only mark files as expired, do not delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired answer files based on retention policy';

    public function __construct(
        private readonly AnswerFileService $answerFileService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $markOnly = $this->option('mark-only');
        $batchSize = $this->option('batch-size')
            ? (int) $this->option('batch-size')
            : config('grading.file_retention.cleanup_batch_size', 100);

        $retentionDays = Answer::getRetentionPeriodDays();

        if ($retentionDays === null) {
            $this->info('File retention is set to unlimited. No files will be cleaned up.');

            return self::SUCCESS;
        }

        $this->info("File retention period: {$retentionDays} days");
        $this->info("Batch size: {$batchSize}");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No files will be modified or deleted');
        }

        // Step 1: Mark eligible files as expired
        $this->markExpiredFiles($retentionDays, $batchSize, $isDryRun);

        // Step 2: Delete expired files (unless mark-only mode)
        if (! $markOnly) {
            $this->deleteExpiredFiles($batchSize, $isDryRun);
        }

        $this->info('Cleanup completed.');

        return self::SUCCESS;
    }

    /**
     * Mark files as expired based on retention period.
     */
    private function markExpiredFiles(int $retentionDays, int $batchSize, bool $isDryRun): void
    {
        $this->info('Marking files as expired...');

        $eligibleCount = Answer::eligibleForExpiration($retentionDays)->count();

        if ($eligibleCount === 0) {
            $this->info('No files eligible for expiration.');

            return;
        }

        $this->info("Found {$eligibleCount} answer(s) with files eligible for expiration.");

        if ($isDryRun) {
            $this->table(
                ['ID', 'Submission ID', 'Question ID', 'Created At', 'File Count'],
                Answer::eligibleForExpiration($retentionDays)
                    ->limit($batchSize)
                    ->get()
                    ->map(fn (Answer $answer) => [
                        $answer->id,
                        $answer->submission_id,
                        $answer->question_id,
                        $answer->created_at->toDateTimeString(),
                        count($answer->file_paths ?? []),
                    ])
            );

            return;
        }

        $markedCount = 0;
        $processedCount = 0;

        // Process in batches
        while ($processedCount < $eligibleCount) {
            $answers = Answer::eligibleForExpiration($retentionDays)
                ->limit($batchSize)
                ->get();

            if ($answers->isEmpty()) {
                break;
            }

            foreach ($answers as $answer) {
                // Build and store metadata before marking as expired
                if (empty($answer->file_metadata)) {
                    $metadata = $this->answerFileService->buildFileMetadata($answer);
                    $answer->storeFileMetadata($metadata);
                }

                if ($answer->markFilesExpired()) {
                    $markedCount++;
                    $this->line("  Marked answer #{$answer->id} files as expired");
                }

                $processedCount++;
            }

            $this->info("Processed {$processedCount}/{$eligibleCount} answers...");
        }

        $this->info("Marked {$markedCount} answer(s) as expired.");
    }

    /**
     * Delete files that have been marked as expired.
     */
    private function deleteExpiredFiles(int $batchSize, bool $isDryRun): void
    {
        $this->info('Deleting expired files...');

        $expiredAnswers = Answer::getExpiredFileAnswers();
        $totalCount = $expiredAnswers->count();

        if ($totalCount === 0) {
            $this->info('No expired files to delete.');

            return;
        }

        $this->info("Found {$totalCount} answer(s) with expired files to delete.");

        if ($isDryRun) {
            $this->table(
                ['ID', 'Submission ID', 'Question ID', 'Expired At', 'File Count'],
                $expiredAnswers->take($batchSize)->map(fn (Answer $answer) => [
                    $answer->id,
                    $answer->submission_id,
                    $answer->question_id,
                    $answer->files_expired_at?->toDateTimeString(),
                    count($answer->file_paths ?? []),
                ])
            );

            return;
        }

        $deletedCount = 0;
        $processedCount = 0;

        foreach ($expiredAnswers as $answer) {
            if ($this->answerFileService->deleteExpiredFiles($answer)) {
                $deletedCount++;
                $this->line("  Deleted files for answer #{$answer->id}");
            }

            $processedCount++;

            if ($processedCount % $batchSize === 0) {
                $this->info("Processed {$processedCount}/{$totalCount} answers...");
            }
        }

        $this->info("Deleted files for {$deletedCount} answer(s).");
    }
}
