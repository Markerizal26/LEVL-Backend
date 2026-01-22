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
 * Job to bulk apply feedback to multiple submissions.
 *
 * This job processes bulk feedback application operations in the background,
 * allowing for efficient handling of large batches without blocking
 * the user interface.
 *
 * Requirements: 26.4 - THE System SHALL support bulk feedback application to selected submissions
 * Requirements: 28.6 - THE System SHALL process updates in background jobs using Laravel queues
 */
class BulkApplyFeedbackJob implements ShouldQueue
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
     * @param  array<int>  $submissionIds  Array of submission IDs to apply feedback to
     * @param  string  $feedback  The feedback text to apply
     * @param  int|null  $instructorId  The ID of the instructor applying the feedback
     */
    public function __construct(
        public array $submissionIds,
        public string $feedback,
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
            Log::info('BulkApplyFeedbackJob: No submission IDs provided, skipping');

            return;
        }

        if (empty(trim($this->feedback))) {
            Log::warning('BulkApplyFeedbackJob: Empty feedback provided, skipping');

            return;
        }

        Log::info('BulkApplyFeedbackJob: Starting bulk feedback application', [
            'submission_count' => count($this->submissionIds),
            'feedback_length' => strlen($this->feedback),
            'instructor_id' => $this->instructorId,
        ]);

        try {
            $result = $gradingService->bulkApplyFeedback($this->submissionIds, $this->feedback);

            Log::info('BulkApplyFeedbackJob: Completed bulk feedback application', [
                'success_count' => $result['success'],
                'failed_count' => $result['failed'],
                'errors' => $result['errors'],
                'instructor_id' => $this->instructorId,
            ]);
        } catch (\Throwable $e) {
            Log::error('BulkApplyFeedbackJob: Failed to apply feedback', [
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
        Log::error('BulkApplyFeedbackJob: Job failed after all retries', [
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
            'bulk-apply-feedback',
            'instructor:'.($this->instructorId ?? 'unknown'),
            'submissions:'.count($this->submissionIds),
        ];
    }
}
