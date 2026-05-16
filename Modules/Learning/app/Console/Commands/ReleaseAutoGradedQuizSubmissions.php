<?php

declare(strict_types=1);

namespace Modules\Learning\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Learning\Enums\QuizSubmissionStatus;
use Modules\Learning\Models\QuizSubmission;

class ReleaseAutoGradedQuizSubmissions extends Command
{
    protected $signature = 'quiz-submissions:release-auto-graded
                            {--quiz= : Limit to a specific quiz ID}
                            {--dry-run : Preview affected submissions without changing data}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Release all graded submissions for auto-graded quizzes so students can see their scores and unlock subsequent content.';

    public function handle(): int
    {
        $query = QuizSubmission::query()
            ->where('status', QuizSubmissionStatus::Graded->value)
            ->whereHas('quiz', fn ($q) => $q->where('auto_grading', true));

        if ($quizId = $this->option('quiz')) {
            $query->where('quiz_id', (int) $quizId);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No graded submissions found for auto-graded quizzes.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("[DRY RUN] {$total} submission(s) would be released.");
            $this->table(
                ['ID', 'Quiz ID', 'User ID', 'Score', 'Final Score', 'Submitted At'],
                (clone $query)->limit(50)->get()->map(fn ($s) => [
                    $s->id,
                    $s->quiz_id,
                    $s->user_id,
                    $s->score,
                    $s->final_score,
                    $s->submitted_at?->toDateTimeString(),
                ])->toArray()
            );

            if ($total > 50) {
                $this->line("... and ".($total - 50)." more.");
            }

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Release {$total} submission(s)? This will make scores visible and unlock subsequent content.")) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $released = 0;

        DB::transaction(function () use ($query, &$released): void {
            $query->orderBy('id')->chunkById(200, function ($submissions) use (&$released): void {
                foreach ($submissions as $submission) {
                    $submission->update(['status' => QuizSubmissionStatus::Released->value]);
                    $released++;
                }
            });
        });

        $this->info("Released {$released} submission(s).");

        return self::SUCCESS;
    }
}
