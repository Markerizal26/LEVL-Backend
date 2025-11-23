<?php

namespace Modules\Assessments\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Modules\Assessments\Models\Attempt;
use Modules\Assessments\Models\Exercise;
use Modules\Assessments\Repositories\AttemptRepository;
use Modules\Auth\Models\User;

class AttemptService
{
    public function __construct(private readonly AttemptRepository $repository) {}

    public function paginate(User $user, array $params, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateForUser($user, $params, $perPage);
    }

    public function start(User $user, Exercise $exercise): Attempt
    {
        $enrollment = $this->repository->findEnrollmentForExercise($user, $exercise);
        if (! $enrollment) {
            $this->validationError([
                'exercise' => ['Anda belum terdaftar pada course yang memuat soal ini.'],
            ]);
        }

        $now = Carbon::now();
        if ($exercise->available_from && $now->lt($exercise->available_from)) {
            $this->validationError([
                'exercise' => ['Soal ini belum tersedia.'],
            ]);
        }

        if ($exercise->available_until && $now->gt($exercise->available_until)) {
            $this->validationError([
                'exercise' => ['Soal ini tidak lagi tersedia.'],
            ]);
        }

        if ($exercise->status !== 'published') {
            $this->validationError([
                'exercise' => ['Soal ini belum dipublikasikan.'],
            ]);
        }

        $attempt = $this->repository->create([
            'exercise_id' => $exercise->id,
            'user_id' => $user->id,
            'enrollment_id' => $enrollment->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'total_questions' => $exercise->questions()->count(),
        ]);

        return $attempt->fresh();
    }

    public function show(Attempt $attempt): Attempt
    {
        return $this->repository->refreshWithDetails($attempt);
    }

    public function submitAnswer(Attempt $attempt, array $data)
    {
        if ($attempt->status !== 'in_progress') {
            $this->validationError([
                'attempt' => ['Upaya jawab ini sudah tidak lagi dalam proses.'],
            ]);
        }

        $conditions = [
            'question_id' => $data['question_id'],
        ];
        $values = [
            'selected_option_id' => $data['selected_option_id'] ?? null,
            'answer_text' => $data['answer_text'] ?? null,
        ];

        $answer = $this->repository->firstOrCreateAnswer($attempt, $conditions, $values);
        if (! $answer->wasRecentlyCreated) {
            $answer = $this->repository->updateAnswer($answer, $values);
        }

        return $answer;
    }

    public function complete(Attempt $attempt): Attempt
    {
        if ($attempt->status !== 'in_progress') {
            $this->validationError([
                'attempt' => ['Upaya jawab ini sudah selesai.'],
            ]);
        }

        $attempt->update([
            'status' => 'completed',
            'finished_at' => now(),
            'duration_seconds' => $attempt->started_at?->diffInSeconds(now()),
        ]);

        $this->gradeAttempt($attempt);

        return $attempt->fresh();
    }

    private function gradeAttempt(Attempt $attempt): void
    {
        $totalScore = 0;
        $correctAnswers = 0;

        $answers = $this->repository->answersWithRelations($attempt);
        foreach ($answers as $answer) {
            $question = $answer->question;
            if (! $question) {
                continue;
            }

            if (in_array($question->type, ['multiple_choice', 'true_false'], true)) {
                $score = 0;
                if ($answer->selectedOption && $answer->selectedOption->is_correct) {
                    $score = $question->score_weight;
                    $correctAnswers++;
                }

                $answer->update(['score_awarded' => $score]);
                $totalScore += $score;
            }
        }

        $attempt->update([
            'score' => $totalScore,
            'correct_answers' => $correctAnswers,
        ]);
    }

    private function validationError(array $messages): void
    {
        throw ValidationException::withMessages($messages);
    }
}
