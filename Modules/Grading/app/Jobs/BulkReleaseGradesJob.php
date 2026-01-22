<?php

declare(strict_types=1);

namespace Modules\Grading\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Grading\Contracts\Services\GradingServiceInterface;

/**
 * Job to bulk release grades for multiple submissions.
 *
 * This job processes bulk grade release operations in the background,
 * allowing for efficient handling of large batches without blocking
 * the user interface.
 *
 * Requirements: 26.2 - THE System SHALL support bulk grade release for submissions in deferred review mode
 * Requirements: 28.6 - THE System SHALL process updates in background jobs using Laravel queues
 */
class BulkReleaseGradesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param  array<int>  $submissionIds  Array of submission IDs to release grades for
     * @param  int|null  $instructorId  The ID of the instructor initiating the release
     */
    public function __construct(
        public array $submissionIds,
        public ?int $instructorId = null
    ) {
        $this->onQueue('grading');
    }

    /**
     * Execute the job.
     */
    public function handle(GradingServiceInterface $gradingService): void
    {
        if (empty($this->submissionIds)) {
            Log::info('BulkReleaseGradesJob: No submission IDs provided, skipping');

            return;
        }

        Log::info('BulkReleaseGradesJob: Starting bulk grade release', [
            'submission_count' => count($this->submissionIds),
            'instructor_id' => $this->instructorId,
        ]);

        try {
            $result = $gradingService->bulkReleaseGrades($this->submissionIds);

            Log::info('BulkReleaseGradesJob: Completed bulk grade release', [
                'success_count' => $result['success'],
                'failed_count' => $result['failed'],
                'errors' => $result['errors'],
                'instructor_id' => $this->instructorId,
            ]);
        } catch (\Throwable $e) {
            Log::error('BulkReleaseGradesJob: Failed to release grades', [
                'submission_ids' => $this->submissionIds,
                'instructor_id' => $this->instructorId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BulkReleaseGradesJob: Job failed after all retries', [
            'submission_ids' => $this->submissionIds,
            'instructor_id' => $this->instructorId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'bulk-release-grades',
            'instructor:'.($this->instructorId ?? 'unknown'),
            'submissions:'.count($this->submissionIds),
        ];
    }
}
