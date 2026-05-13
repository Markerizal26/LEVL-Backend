<?php

declare(strict_types=1);

namespace Modules\Learning\Services\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Grading\Services\GradingEntryService;
use Modules\Learning\Contracts\Repositories\SubmissionRepositoryInterface;
use Modules\Learning\Enums\QuestionType;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Enums\SubmissionStatus;
use Modules\Learning\Events\NewHighScoreAchieved;
use Modules\Learning\Exceptions\SubmissionException;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Submission;

class SubmissionCompletionProcessor
{
    private const TIMER_GRACE_SECONDS = 60;

    public function __construct(
        private readonly SubmissionRepositoryInterface $repository,
        private readonly GradingEntryService $gradingEntryService
    ) {}

    public function update(Submission $submission, array $data): Submission
    {
        return DB::transaction(function () use ($submission, $data) {
            if ($submission->status === SubmissionStatus::Graded) {
                throw SubmissionException::alreadyGraded();
            }

            $updated = $this->repository->update($submission, [
                'answer_text' => $data['answer_text'] ?? $submission->answer_text,
            ]);

            if (isset($data['files']) && is_array($data['files'])) {
                $updated->clearMediaCollection('submission_files');

                foreach ($data['files'] as $file) {
                    if ($file instanceof UploadedFile) {
                        $updated->addMedia($file)
                            ->toMediaCollection('submission_files');
                    }
                }
            }

            return $updated->fresh(['assignment', 'user', 'enrollment', 'media']);
        });
    }

    public function grade(Submission $submission, int $score, int $gradedBy, ?string $feedback = null): Submission
    {
        return DB::transaction(function () use ($submission, $score, $gradedBy, $feedback) {
            $assignment = $submission->assignment;
            $maxScore = $assignment->max_score;

            if ($score < 0 || $score > $maxScore) {
                throw SubmissionException::invalidScore(__('messages.submissions.score_out_of_range', ['max' => $maxScore]));
            }

            $dto = new \Modules\Grading\DTOs\SubmissionGradeDTO(
                submissionId: $submission->id,
                answers: ['score' => $score],
                scoreOverride: (float) $score,
                feedback: $feedback,
                graderId: $gradedBy
            );

            $grade = $this->gradingEntryService->manualGrade($dto);

            $updated = $this->repository->update($submission, [
                'status' => SubmissionStatus::Graded->value,
            ])->fresh(['assignment', 'user', 'enrollment', 'media']);
            $updated->setRelation('grade', $grade);

            return $updated;
        });
    }

    public function saveAnswer(Submission $submission, int $questionId, mixed $answer): \Modules\Learning\Models\Answer
    {
        return DB::transaction(function () use ($submission, $questionId, $answer) {
            $assignment = $submission->assignment;

            if ($submission->state !== SubmissionState::InProgress) {
                throw SubmissionException::notAllowed(__('messages.submissions.cannot_modify'));
            }

            $this->validateQuestionInAssignment($submission, $assignment, $questionId);

            $question = Question::find($questionId);

            $data = $this->prepareAnswerData($submission->id, $questionId, $question, $answer);

            $createdAnswer = \Modules\Learning\Models\Answer::updateOrCreate(
                ['submission_id' => $submission->id, 'question_id' => $questionId],
                $data
            );

            if ($question->type === QuestionType::FileUpload && $answer) {
                $this->handleFileUpload($createdAnswer, $answer);
            }

            return $createdAnswer->withoutRelations();
        });
    }

    public function submitAnswers(int $submissionId, array $answers): Submission
    {
        $submission = DB::transaction(function () use ($submissionId, $answers) {
            $submission = Submission::findOrFail($submissionId);

            if ($submission->state !== SubmissionState::InProgress) {
                throw SubmissionException::notAllowed(__('messages.submissions.cannot_modify'));
            }

            $this->processAnswers($submission, $answers);

            $submission->update([
                'status' => SubmissionStatus::Submitted->value,
                'submitted_at' => Carbon::now(),
            ]);

            return $submission->fresh(['assignment', 'user', 'answers']);
        });

        try {
            $this->gradingEntryService->autoGrade($submission->id);
            $submission->refresh();
        } catch (\Exception $e) {
            report($e);
        }

        return $submission->fresh(['assignment', 'user', 'answers']);
    }

    public function updateSubmissionScore(Submission $submission, float $score): Submission
    {
        $submission->update(['score' => $score]);
        $this->checkAndDispatchNewHighScore($submission);

        return $submission->fresh();
    }

    public function delete(Submission $submission): bool
    {
        return DB::transaction(fn () => $this->repository->delete($submission));
    }

    private function validateQuestionInAssignment(Submission $submission, mixed $assignment, int $questionId): void
    {
        if ($submission->question_set && ! in_array($questionId, $submission->question_set)) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.submissions.question_not_in_set'));
        } elseif (! $submission->question_set) {
            $exists = Question::where('id', $questionId)
                ->where('assignment_id', $assignment->id)
                ->exists();
            if (! $exists) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.submissions.question_not_in_assignment'));
            }
        }
    }

    private function prepareAnswerData(int $submissionId, int $questionId, Question $question, mixed $answer): array
    {
        $data = [
            'submission_id' => $submissionId,
            'question_id' => $questionId,
        ];

        if ($question->type->requiresOptions()) {
            $data['selected_options'] = (array) $answer;
        } elseif ($question->type === QuestionType::FileUpload) {
            if (is_array($answer) && ! empty($answer) && is_string(reset($answer))) {
                $data['file_paths'] = $answer;
            }
        } else {
            $data['content'] = (string) $answer;
        }

        return $data;
    }

    private function handleFileUpload(mixed $createdAnswer, mixed $answer): void
    {
        $createdAnswer->clearMediaCollection('answers');
        $files = is_array($answer) ? $answer : [$answer];
        $paths = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $media = $createdAnswer->addMedia($file)
                    ->toMediaCollection('answers', config('filesystems.default', 'do'));
                $paths[] = $media->getUrl();
            }
        }

        $createdAnswer->update(['file_paths' => $paths]);
    }

    private function processAnswers(Submission $submission, array $answers): void
    {
        if (empty($answers)) {
            return;
        }

        foreach ($answers as $answerData) {
            if (empty($answerData['question_id'])) {
                continue;
            }

            $val = null;
            if (isset($answerData['selected_options'])) {
                $val = $answerData['selected_options'];
            } elseif (isset($answerData['file_paths'])) {
                $val = $answerData['file_paths'];
            } elseif (isset($answerData['content'])) {
                $val = $answerData['content'];
            }

            if ($val !== null) {
                $this->saveAnswer($submission, (int) $answerData['question_id'], $val);
            }
        }
    }

    public function checkAndDispatchNewHighScore(Submission $submission): void
    {
        if ($submission->score === null) {
            return;
        }

        $otherSubmissions = Submission::query()
            ->where('assignment_id', $submission->assignment_id)
            ->where('user_id', $submission->user_id)
            ->where('id', '!=', $submission->id)
            ->whereNotNull('score')
            ->whereNotIn('state', [SubmissionState::InProgress->value])
            ->get();

        $previousHighScore = $otherSubmissions->max('score');

        if ($previousHighScore === null || $submission->score > $previousHighScore) {
            NewHighScoreAchieved::dispatch(
                $submission,
                $previousHighScore,
                $submission->score
            );
        }
    }
}
