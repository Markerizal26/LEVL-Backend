<?php

declare(strict_types=1);

namespace Modules\Learning\Database\Seeders;

use App\Support\RealisticSeederContent;
use App\Support\SeederDate;
use App\Support\UATMediaFixtures;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\QuizGradingStatus;
use Modules\Learning\Enums\QuizQuestionType;
use Modules\Learning\Enums\QuizStatus;
use Modules\Learning\Enums\QuizSubmissionStatus;
use Modules\Learning\Enums\RandomizationType;
use Modules\Learning\Enums\ReviewMode;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Enums\SubmissionStatus;
use Modules\Learning\Enums\SubmissionType;
use Modules\Learning\Models\Submission;

class ComprehensiveAssessmentSeeder extends Seeder
{
    private array $pregenSentences = [];

    private array $pregenParagraphs = [];

    private array $pregenWords = [];

    private array $pregenUuids = [];

    private string $createdAt;

    private array $userIds = [];

    private array $instructorIds = [];

    public function run(): void
    {
        DB::connection()->disableQueryLog();
        ini_set('memory_limit', '1536M');

        echo "\n🎯 Comprehensive Assessment Seeder (Refactored)\n";
        echo '='.str_repeat('=', 50)."\n";

        if (DB::table('assignments')->count() > 0 || DB::table('quizzes')->count() > 0) {
            echo "ℹ️  Assessment data already seeded. Skipping to keep idempotency.\n";

            return;
        }

        $this->pregenerateFakeData();
        $this->createdAt = SeederDate::randomPastDateTimeBetween(14, 180);

        $this->userIds = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'Student')
            ->where('model_has_roles.model_type', 'Modules\\Auth\\Models\\User')
            ->limit(50)
            ->pluck('users.id')
            ->toArray();

        $this->instructorIds = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('roles.name', ['Instructor', 'Admin'])
            ->where('model_has_roles.model_type', 'Modules\\Auth\\Models\\User')
            ->pluck('users.id')
            ->toArray();

        if (empty($this->userIds)) {
            echo "⚠️  No users found. Please run user seeders first.\n";

            return;
        }

        if (empty($this->instructorIds)) {
            $this->instructorIds = DB::table('users')->limit(5)->pluck('id')->toArray();
        }

        $courses = DB::table('courses')->select('id', 'title')->get();

        if ($courses->isEmpty()) {
            echo "⚠️  No courses found. Please run course seeders first.\n";

            return;
        }

        echo "� Found {$courses->count()} courses\n\n";

        $totalAssignments = 0;
        $totalQuizzes = 0;
        $totalQuestions = 0;
        $totalSubmissions = 0;
        $totalAnswers = 0;
        $totalGrades = 0;

        foreach ($courses as $index => $course) {
            echo '📘 Course '.($index + 1)."/{$courses->count()}: {$course->title}\n";

            $units = DB::table('units')
                ->where('course_id', $course->id)
                ->select('id', 'title')
                ->get();

            if ($units->isEmpty()) {
                echo "   ⚠️ No units found, skipping...\n";

                continue;
            }

            foreach ($units as $unit) {
                $result = $this->createMixedAssessments($course->id, $unit->id);
                $totalAssignments += $result['assignments'];
                $totalQuizzes += $result['quizzes'];
                $totalQuestions += $result['questions'];
                $totalSubmissions += $result['submissions'];
                $totalAnswers += $result['answers'];
                $totalGrades += $result['grades'];
            }

            echo "   ✓ Created data for course\n";

            if (($index + 1) % 5 === 0) {
                gc_collect_cycles();
            }
        }

        echo "\n".str_repeat('=', 50)."\n";
        echo "✅ Comprehensive Assessment Seeding Completed!\n";
        echo "   📊 Assignments: {$totalAssignments}\n";
        echo "   📊 Quizzes: {$totalQuizzes}\n";
        echo "   📊 Questions: {$totalQuestions}\n";
        echo "   📊 Submissions: {$totalSubmissions}\n";
        echo "   📊 Answers: {$totalAnswers}\n";
        echo "   📊 Grades: {$totalGrades}\n";

        $this->cleanup();
        DB::connection()->enableQueryLog();
    }

    private function createMixedAssessments(int $courseId, int $unitId): array
    {
        $stats = ['assignments' => 0, 'quizzes' => 0, 'questions' => 0, 'submissions' => 0, 'answers' => 0, 'grades' => 0];

        $numAssignments = rand(1, 3);
        $numQuizzes = rand(1, 3);

        $maxLessonOrder = DB::table('lessons')->where('unit_id', $unitId)->max('order') ?? 0;
        $currentOrder = $maxLessonOrder + 1;

        $assignments = [];
        $quizzes = [];

        for ($i = 0; $i < $numAssignments; $i++) {
            $result = $this->createAssignmentWithSubmissions($courseId, $unitId, $currentOrder);
            $assignments[] = DB::table('assignments')->where('unit_id', $unitId)->latest('id')->first();
            $stats['assignments'] += $result['assignments'];
            $stats['submissions'] += $result['submissions'];
            $stats['grades'] += $result['grades'];
            $currentOrder++;
        }

        for ($i = 0; $i < $numQuizzes; $i++) {
            $result = $this->createQuizWithSubmissions($courseId, $unitId, $currentOrder);
            $quizzes[] = DB::table('quizzes')->where('unit_id', $unitId)->latest('id')->first();
            $stats['quizzes'] += $result['quizzes'];
            $stats['questions'] += $result['questions'];
            $stats['submissions'] += $result['submissions'];
            $stats['answers'] += $result['answers'];
            $currentOrder++;
        }

        $this->shuffleUnitContentOrder($unitId);

        return $stats;
    }

    private function shuffleUnitContentOrder(int $unitId): void
    {
        $items = DB::table('unit_contents')
            ->where('unit_id', $unitId)
            ->get()
            ->shuffle()
            ->values();

        if ($items->isEmpty()) {
            return;
        }

        DB::table('unit_contents')
            ->where('unit_id', $unitId)
            ->update(['order' => DB::raw('-1 * id')]);

        foreach ($items as $index => $item) {
            $newOrder = $index + 1;

            DB::table('unit_contents')
                ->where('id', $item->id)
                ->update(['order' => $newOrder]);

            $table = match ($item->contentable_type) {
                'lesson' => 'lessons',
                'assignment' => 'assignments',
                'quiz' => 'quizzes',
                default => null,
            };

            if ($table) {
                DB::table($table)
                    ->where('id', $item->contentable_id)
                    ->update(['order' => $newOrder]);
            }
        }
    }

    private function createAssignmentWithSubmissions(int $courseId, int $unitId, int $order): array
    {
        $stats = ['assignments' => 0, 'submissions' => 0, 'grades' => 0];

        $rand = rand(1, 100);
        if ($rand <= 60) {
            $submissionType = 'file';
        } elseif ($rand <= 85) {
            $submissionType = 'text';
        } else {
            $submissionType = 'mixed';
        }

        $assignmentStatus = $this->pickAssignmentStatus();

        $assignmentId = DB::table('assignments')->insertGetId([
            'unit_id' => $unitId,
            'order' => $order,
            'created_by' => $this->instructorIds[array_rand($this->instructorIds)],
            'title' => $this->pregenSentences[array_rand($this->pregenSentences)],
            'description' => $this->pregenParagraphs[array_rand($this->pregenParagraphs)],
            'submission_type' => $submissionType,
            'review_mode' => $this->reviewModeForOrder($order),
            'max_score' => 100,
            'passing_grade' => rand(60, 80),
            'status' => $assignmentStatus,
            'created_at' => $this->createdAt,
            'updated_at' => $this->createdAt,
        ]);
        $stats['assignments']++;

        DB::table('unit_contents')->insert([
            'unit_id' => $unitId,
            'contentable_type' => 'assignment',
            'contentable_id' => $assignmentId,
            'order' => $order,
            'created_at' => $this->createdAt,
            'updated_at' => $this->createdAt,
        ]);

        if (rand(0, 1)) {
            $this->createAssignmentAttachments($assignmentId);
        }

        if ($assignmentStatus !== AssignmentStatus::Published->value) {
            return $stats;
        }

        $selectedUsers = array_slice($this->userIds, 0, min(5, count($this->userIds)));
        foreach ($selectedUsers as $userId) {
            $submissionResult = $this->createSubmissionWithGrade($assignmentId, $userId, $courseId);
            $stats['submissions'] += $submissionResult['submissions'];
            $stats['grades'] += $submissionResult['grades'];
        }

        return $stats;
    }

    private function createQuizWithSubmissions(int $courseId, int $unitId, int $order): array
    {
        $stats = ['quizzes' => 0, 'questions' => 0, 'submissions' => 0, 'answers' => 0];

        $quizStatus = $this->pickQuizStatus();
        $randomization = $this->randomizationTypeForOrder($order);

        $quizId = DB::table('quizzes')->insertGetId([
            'unit_id' => $unitId,
            'order' => $order,
            'created_by' => $this->instructorIds[array_rand($this->instructorIds)],
            'title' => 'Quiz: '.$this->pregenSentences[array_rand($this->pregenSentences)],
            'description' => $this->pregenParagraphs[array_rand($this->pregenParagraphs)],
            'passing_grade' => rand(60, 80),
            'auto_grading' => \DB::raw('true'),
            'max_score' => 100,

            'review_mode' => $this->reviewModeForOrder($order),
            'randomization_type' => $randomization,
            'question_bank_count' => $randomization === RandomizationType::Bank->value ? rand(8, 20) : null,
            'status' => $quizStatus,
            'created_at' => $this->createdAt,
            'updated_at' => $this->createdAt,
        ]);
        $stats['quizzes']++;

        DB::table('unit_contents')->insert([
            'unit_id' => $unitId,
            'contentable_type' => 'quiz',
            'contentable_id' => $quizId,
            'order' => $order,
            'created_at' => $this->createdAt,
            'updated_at' => $this->createdAt,
        ]);

        $questionTypes = [
            QuizQuestionType::MultipleChoice->value,
            QuizQuestionType::TrueFalse->value,
            QuizQuestionType::Checkbox->value,
            QuizQuestionType::Essay->value,
        ];

        $questions = [];
        foreach ($questionTypes as $order => $type) {
            $questionId = $this->createQuizQuestion($quizId, $type, $order + 1);
            $questions[] = ['id' => $questionId, 'type' => $type, 'weight' => 25];
            $stats['questions']++;
        }

        if ($quizStatus !== QuizStatus::Published->value) {
            return $stats;
        }

        $selectedUsers = array_slice($this->userIds, 0, min(5, count($this->userIds)));

        foreach ($selectedUsers as $userId) {
            $submissionResult = $this->createQuizSubmissionWithAnswers($quizId, $userId, $questions);
            $stats['submissions'] += $submissionResult['submissions'];
            $stats['answers'] += $submissionResult['answers'];
        }

        return $stats;
    }

    private function createSubmissionWithGrade(int $assignmentId, int $userId, int $courseId): array
    {
        $stats = ['submissions' => 0, 'grades' => 0];

        $assignment = DB::table('assignments')->where('id', $assignmentId)->first();
        if (! $assignment) {
            return $stats;
        }

        $passingGrade = (float) ($assignment->passing_grade ?? 75);
        $maxScore = (float) ($assignment->max_score ?? 100);
        $submissionType = SubmissionType::tryFrom((string) ($assignment->submission_type ?? 'text'))
            ?? SubmissionType::Text;

        $enrollmentId = DB::table('enrollments')
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->value('id');

        $roll = rand(1, 100);
        $state = match (true) {
            $roll <= 12 => SubmissionState::InProgress,
            $roll <= 58 => SubmissionState::PendingManualGrading,
            $roll <= 72 => SubmissionState::AutoGraded,
            $roll <= 88 => SubmissionState::Graded,
            default => SubmissionState::Released,
        };

        $status = match ($state) {
            SubmissionState::InProgress => SubmissionStatus::Draft,
            SubmissionState::PendingManualGrading => SubmissionStatus::Submitted,
            SubmissionState::AutoGraded,
            SubmissionState::Graded,
            SubmissionState::Released => SubmissionStatus::Graded,
        };

        $submittedAt = $state === SubmissionState::InProgress
            ? null
            : SeederDate::randomPastDateTimeBetween(1, 120);

        $nextAttempt = (int) (DB::table('submissions')
            ->where('assignment_id', $assignmentId)
            ->where('user_id', $userId)
            ->max('attempt_number') ?? 0) + 1;

        $isSubmittedLike = $state !== SubmissionState::InProgress;

        $includeText = false;
        $includeFile = false;

        match ($submissionType) {
            SubmissionType::Text => $includeText = true,
            SubmissionType::File => $includeFile = true,
            SubmissionType::Mixed => match (rand(1, 3)) {
                1 => $includeText = true,
                2 => $includeFile = true,
                default => $includeText = $includeFile = true,
            },
        };

        if ($isSubmittedLike && $submissionType === SubmissionType::Mixed && ! $includeText && ! $includeFile) {
            $includeFile = true;
        }

        $answerText = null;
        if ($includeText) {
            $answerText = $this->pregenParagraphs[array_rand($this->pregenParagraphs)];
            if (mb_strlen((string) $answerText) < 10) {
                $answerText .= ' '.str_repeat('x', 10 - mb_strlen((string) $answerText));
            }
        }

        if ($isSubmittedLike && $submissionType === SubmissionType::Text) {
            $answerText = $this->pregenParagraphs[array_rand($this->pregenParagraphs)];
            if (mb_strlen((string) $answerText) < 10) {
                $answerText .= ' '.str_repeat('x', 10 - mb_strlen((string) $answerText));
            }
        }

        if ($isSubmittedLike && $submissionType === SubmissionType::File) {
            $includeFile = true;
        }

        $totalScore = null;
        if (in_array($state, [SubmissionState::Graded, SubmissionState::Released, SubmissionState::AutoGraded], true)) {
            $cap = (int) max(1, min(100, (int) round($maxScore)));
            $minScore = min($cap, max(1, (int) floor($passingGrade * 0.6)));
            $maxScoreInt = $cap;
            if ($minScore > $maxScoreInt) {
                $minScore = $maxScoreInt;
            }
            $totalScore = (float) rand($minScore, $maxScoreInt);
        }

        $submission = Submission::create([
            'assignment_id' => $assignmentId,
            'user_id' => $userId,
            'enrollment_id' => $enrollmentId,
            'answer_text' => $answerText,
            'status' => $status,
            'state' => $state->value,
            'submitted_at' => $submittedAt,
            'attempt_number' => $nextAttempt,
            'score' => $totalScore,
        ]);
        $stats['submissions']++;

        if ($includeFile) {
            $mustAttach = $isSubmittedLike
                || $submissionType === SubmissionType::File
                || rand(1, 100) <= 55;
            if ($mustAttach) {
                $this->attachFixturePdfToSubmission($submission);
            }
        }

        if ($state === SubmissionState::PendingManualGrading) {
            DB::table('grades')->insertOrIgnore([
                'submission_id' => $submission->id,
                'source_type' => 'assignment',
                'source_id' => $assignmentId,
                'user_id' => $userId,
                'graded_by' => $this->instructorIds[array_rand($this->instructorIds)],
                'score' => null,
                'max_score' => $maxScore,
                'feedback' => null,
                'status' => 'pending',
                'is_draft' => \DB::raw('true'),
                'graded_at' => null,
                'released_at' => null,
                'created_at' => $this->createdAt,
                'updated_at' => $this->createdAt,
            ]);
            $stats['grades']++;
        }

        if (in_array($state, [SubmissionState::Graded, SubmissionState::Released, SubmissionState::AutoGraded], true)) {
            DB::table('grades')->insertOrIgnore([
                'submission_id' => $submission->id,
                'source_type' => 'assignment',
                'source_id' => $assignmentId,
                'user_id' => $userId,
                'graded_by' => $this->instructorIds[array_rand($this->instructorIds)],
                'score' => $totalScore,
                'max_score' => $maxScore,
                'feedback' => $this->pregenParagraphs[array_rand($this->pregenParagraphs)],
                'status' => 'graded',
                'is_draft' => \DB::raw('false'),
                'graded_at' => SeederDate::randomPastDateTimeBetween(7, 180),
                'released_at' => $state === SubmissionState::Released ? SeederDate::randomPastDateTimeBetween(1, 120) : null,
                'created_at' => $this->createdAt,
                'updated_at' => $this->createdAt,
            ]);
            $stats['grades']++;
        }

        return $stats;
    }

    private function attachFixturePdfToSubmission(Submission $submission): void
    {
        UATMediaFixtures::ensureFilesExist();
        $paths = UATMediaFixtures::paths();
        $dummyFilePath = isset($paths['pdf']) && is_string($paths['pdf']) ? $paths['pdf'] : '';
        $fallback = public_path('dummy/pdf-sample_0.pdf');

        if ($dummyFilePath !== '' && ! file_exists($dummyFilePath) && file_exists($fallback)) {
            $dummyFilePath = $fallback;
        }

        if ($dummyFilePath === '' || ! file_exists($dummyFilePath)) {
            return;
        }

        try {
            $submission->addMedia((string) $dummyFilePath)
                ->preservingOriginal()
                ->usingName('submission-'.$submission->id)
                ->usingFileName('submission-'.$submission->id.'.pdf')
                ->toMediaCollection('submission_files', 'do');
        } catch (\Throwable) {

        }
    }

    private function createQuizQuestion(int $quizId, string $type, int $order): int
    {
        $questionData = [
            'quiz_id' => $quizId,
            'type' => $type,
            'content' => $this->getQuizQuestionContent($type),
            'weight' => 25,
            'order' => $order,
            'max_score' => 25,
            'options' => null,
            'answer_key' => null,
            'created_at' => $this->createdAt,
            'updated_at' => $this->createdAt,
        ];

        switch ($type) {
            case QuizQuestionType::MultipleChoice->value:
                $options = ['Option A', 'Option B', 'Option C', 'Option D'];
                $questionData['options'] = json_encode($options);
                $questionData['answer_key'] = json_encode([0]);
                break;

            case QuizQuestionType::TrueFalse->value:
                $questionData['options'] = json_encode(['True', 'False']);
                $questionData['answer_key'] = json_encode([0]);
                break;

            case QuizQuestionType::Checkbox->value:
                $options = ['Option 1', 'Option 2', 'Option 3', 'Option 4'];
                $questionData['options'] = json_encode($options);
                $questionData['answer_key'] = json_encode([0, 2]);
                break;
        }

        return DB::table('quiz_questions')->insertGetId($questionData);
    }

    private function createQuizSubmissionWithAnswers(int $quizId, int $userId, array $questions): array
    {
        $stats = ['submissions' => 0, 'answers' => 0];

        $quiz = DB::table('quizzes')->where('id', $quizId)->first();
        $passingGrade = (float) ($quiz->passing_grade ?? 75);

        $courseId = DB::table('units')
            ->where('id', $quiz->unit_id ?? 0)
            ->value('course_id');

        $enrollmentId = $courseId
            ? DB::table('enrollments')
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('status', 'active')
                ->value('id')
            : null;

        $attemptNumber = (int) (DB::table('quiz_submissions')
            ->where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->max('attempt_number') ?? 0) + 1;

        $daysAgo = rand(1, 14);
        $startedAt = SeederDate::randomPastCarbonBetween($daysAgo, $daysAgo)->subMinutes(rand(10, 120))->toDateTimeString();
        $submittedAt = SeederDate::randomPastCarbonBetween($daysAgo, $daysAgo)->toDateTimeString();

        $scenarioRoll = rand(1, 100);
        $scenario = match (true) {
            $scenarioRoll <= 20 => 'draft',
            $scenarioRoll <= 55 => 'submitted',
            $scenarioRoll <= 80 => 'graded',
            $scenarioRoll <= 95 => 'released',
            default => 'missing',
        };

        $status = match ($scenario) {
            'draft' => QuizSubmissionStatus::Draft->value,
            'submitted' => QuizSubmissionStatus::Submitted->value,
            'graded' => QuizSubmissionStatus::Graded->value,
            'released' => QuizSubmissionStatus::Released->value,
            default => QuizSubmissionStatus::Missing->value,
        };

        $gradingStatus = match ($scenario) {
            'draft' => QuizGradingStatus::Pending->value,
            'submitted' => QuizGradingStatus::PartiallyGraded->value,
            'graded' => QuizGradingStatus::Graded->value,
            'released' => QuizGradingStatus::Released->value,
            default => QuizGradingStatus::Pending->value,
        };

        $submissionId = DB::table('quiz_submissions')->insertGetId([
            'quiz_id' => $quizId,
            'user_id' => $userId,
            'enrollment_id' => $enrollmentId,
            'status' => $status,
            'grading_status' => $gradingStatus,
            'score' => null,
            'final_score' => null,
            'submitted_at' => in_array($scenario, ['draft', 'missing'], true) ? null : $submittedAt,
            'started_at' => $startedAt,
            'attempt_number' => $attemptNumber,
            'created_at' => $this->createdAt,
            'updated_at' => $this->createdAt,
        ]);
        $stats['submissions']++;

        if ($scenario === 'missing') {
            return $stats;
        }

        $objectiveWeightTotal = 0.0;
        $objectiveEarned = 0.0;
        $essayScore = 0.0;

        foreach ($questions as $question) {
            $isEssay = $question['type'] === QuizQuestionType::Essay->value;
            $score = $this->createQuizAnswer($submissionId, $question, $passingGrade, $scenario);
            $stats['answers']++;

            if ($isEssay) {

                if (in_array($scenario, ['graded', 'released'], true)) {
                    $essayScore = $score;
                }
            } else {
                $objectiveWeightTotal += (float) $question['weight'];
                $objectiveEarned += $score;
            }
        }

        $maxScore = (float) ($quiz->max_score ?? 100);
        $objectiveScore = $objectiveWeightTotal > 0
            ? round(($objectiveEarned / $objectiveWeightTotal) * $maxScore, 2)
            : 0.0;

        if ($scenario === 'draft') {

            return $stats;
        }

        if ($scenario === 'submitted') {

            DB::table('quiz_submissions')->where('id', $submissionId)->update([
                'score' => $objectiveScore,
                'final_score' => null,
                'updated_at' => $this->createdAt,
            ]);

            return $stats;
        }

        $finalScore = min((float) $maxScore, round($objectiveScore + $essayScore, 2));

        DB::table('quiz_submissions')->where('id', $submissionId)->update([
            'score' => $finalScore,
            'final_score' => $finalScore,
            'updated_at' => $this->createdAt,
        ]);

        return $stats;
    }

    private function createQuizAnswer(int $submissionId, array $question, float $passingGrade, string $scenario): float
    {
        $answerData = [
            'quiz_submission_id' => $submissionId,
            'quiz_question_id' => $question['id'],
            'content' => null,
            'selected_options' => null,
            'score' => 0,
            'is_auto_graded' => 1,
            'feedback' => null,
            'created_at' => $this->createdAt,
            'updated_at' => $this->createdAt,
        ];

        $isCorrect = rand(0, 100) > 20;

        switch ($question['type']) {
            case QuizQuestionType::MultipleChoice->value:
            case QuizQuestionType::TrueFalse->value:
                $answerData['selected_options'] = json_encode([$isCorrect ? 0 : 1]);
                $answerData['score'] = $isCorrect ? $question['weight'] : 0;
                break;

            case QuizQuestionType::Checkbox->value:
                $answerData['selected_options'] = json_encode($isCorrect ? [0, 2] : [0, 1]);
                $answerData['score'] = $isCorrect ? $question['weight'] : 0;
                break;

            case QuizQuestionType::Essay->value:
                $answerData['content'] = $this->pregenParagraphs[array_rand($this->pregenParagraphs)];
                $answerData['is_auto_graded'] = 0;
                if (in_array($scenario, ['graded', 'released'], true)) {
                    $answerData['score'] = rand((int) ($question['weight'] * 0.7), $question['weight']);
                    $answerData['feedback'] = $this->pregenSentences[array_rand($this->pregenSentences)];
                } else {
                    $answerData['score'] = null;
                    $answerData['feedback'] = null;
                }
                break;
        }

        DB::table('quiz_answers')->insertOrIgnore($answerData);

        return (float) ($answerData['score'] ?? 0);
    }

    private function pickAssignmentStatus(): string
    {
        $roll = rand(1, 100);

        return match (true) {
            $roll <= 10 => AssignmentStatus::Draft->value,
            $roll <= 15 => AssignmentStatus::Archived->value,
            default => AssignmentStatus::Published->value,
        };
    }

    private function pickQuizStatus(): string
    {
        $roll = rand(1, 100);

        return match (true) {
            $roll <= 10 => QuizStatus::Draft->value,
            $roll <= 15 => QuizStatus::Archived->value,
            default => QuizStatus::Published->value,
        };
    }

    private function reviewModeForOrder(int $order): string
    {
        $modes = ReviewMode::cases();

        return $modes[($order - 1) % count($modes)]->value;
    }

    private function randomizationTypeForOrder(int $order): string
    {
        $types = RandomizationType::cases();

        return $types[($order - 1) % count($types)]->value;
    }

    private function getQuizQuestionContent(string $type): string
    {
        $templates = [
            QuizQuestionType::MultipleChoice->value => 'Which of the following best describes dependency injection?',
            QuizQuestionType::TrueFalse->value => 'Laravel uses the MVC architectural pattern.',
            QuizQuestionType::Checkbox->value => 'Select all valid HTTP methods:',
            QuizQuestionType::Essay->value => 'Explain the concept of middleware in web applications.',
        ];

        return $templates[$type] ?? 'Sample question';
    }

    private function createAssignmentAttachments(int $assignmentId): void
    {
        $numAttachments = rand(1, 3);

        for ($i = 0; $i < $numAttachments; $i++) {
            DB::table('media')->insert([
                'model_type' => 'Modules\\Learning\\Models\\Assignment',
                'model_id' => $assignmentId,
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'collection_name' => 'attachments',
                'name' => 'assignment-attachment-'.$i,
                'file_name' => 'document-'.rand(1000, 9999).'.pdf',
                'mime_type' => 'application/pdf',
                'disk' => 'do',
                'conversions_disk' => 'do',
                'size' => rand(100000, 5000000),
                'manipulations' => '[]',
                'custom_properties' => '[]',
                'generated_conversions' => '[]',
                'responsive_images' => '[]',
                'order_column' => $i + 1,
                'created_at' => $this->createdAt,
                'updated_at' => $this->createdAt,
            ]);
        }
    }

    private function pregenerateFakeData(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $this->pregenSentences[] = RealisticSeederContent::assessmentSentence($i);
            $this->pregenParagraphs[] = RealisticSeederContent::paragraph($i);
            $this->pregenWords[] = RealisticSeederContent::wordToken($i);
            $this->pregenUuids[] = RealisticSeederContent::stableUuid($i);
        }
    }

    private function cleanup(): void
    {
        $this->pregenSentences = [];
        $this->pregenParagraphs = [];
        $this->pregenWords = [];
        $this->pregenUuids = [];
        gc_collect_cycles();
    }
}
