<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Submission;
use Modules\Learning\Models\Answer;
use Modules\Learning\Enums\QuestionType;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Enums\SubmissionStatus;

class QuestionOptionAnswerSubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates comprehensive question, option, answer, and submission data:
     * - Questions with different types (multiple choice, essay, etc.)
     * - Options for multiple choice questions
     * - Answers for submissions
     * - Submissions in various states
     */
    public function run(): void
    {
        echo "Seeding questions, options, answers, and submissions...\n";

        // Check if we have users and assignments to link to
        $users = User::all();
        $assignments = Assignment::all();

        if ($users->isEmpty()) {
            echo "⚠️  No users found. Please run user seeders first.\n";
            return;
        }

        if ($assignments->isEmpty()) {
            echo "⚠️  No assignments found. Please run assignment seeders first.\n";
            return;
        }

        // Define question types to use
        $questionTypes = [
            QuestionType::MultipleChoice,
            QuestionType::Essay,
            QuestionType::Checkbox,
            QuestionType::FileUpload,
        ];

        // Create questions for assignments
        $questionCount = 0;
        $optionCount = 0;
        $answerCount = 0;
        $submissionCount = 0;
        $answers = []; // Array to store answers for batch insertion

        foreach ($assignments as $assignment) {
            // Create 3-8 questions per assignment
            $numQuestions = rand(3, 8);

            for ($i = 0; $i < $numQuestions; $i++) {
                $questionType = $questionTypes[array_rand($questionTypes)];

                // Prepare question data based on type
                $questionData = [
                    'assignment_id' => $assignment->id,
                    'type' => $questionType,
                    'content' => fake()->sentence(10),
                    'weight' => fake()->randomFloat(2, 0.5, 5),
                    'order' => $i + 1,
                    'max_score' => fake()->randomElement([10, 20, 25, 50, 100]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Add type-specific fields
                switch ($questionType) {
                    case QuestionType::MultipleChoice:
                    case QuestionType::Checkbox:
                        $options = [
                            ['id' => fake()->uuid(), 'label' => fake()->sentence(3)],
                            ['id' => fake()->uuid(), 'label' => fake()->sentence(3)],
                            ['id' => fake()->uuid(), 'label' => fake()->sentence(3)],
                            ['id' => fake()->uuid(), 'label' => fake()->sentence(3)],
                        ];

                        $questionData['options'] = $options;
                        $questionData['answer_key'] = ['correct_option' => 0]; // Index of correct option
                        break;

                    case QuestionType::Essay:
                        $questionData['answer_key'] = ['acceptable_answers' => [
                            fake()->word(),
                            fake()->word(),
                            fake()->word(),
                        ]];
                        break;

                    case QuestionType::FileUpload:
                        $questionData['max_file_size'] = 10000000; // 10MB
                        $questionData['allowed_file_types'] = ['pdf', 'docx', 'txt', 'png', 'jpg'];
                        $questionData['allow_multiple_files'] = fake()->boolean(30);
                        break;

                    default:
                        // For other types, we might not need specific fields
                        break;
                }

                $question = Question::create($questionData);
                $questionCount++;

                // Create submissions for this assignment
                $numSubmissions = rand(5, 20); // Create 5-20 submissions per assignment

                foreach ($users->random(rand(5, min(20, $users->count()))) as $user) {
                    // Create submission
                    $submissionState = fake()->randomElement([
                        SubmissionState::InProgress,
                        SubmissionState::Submitted,
                        SubmissionState::AutoGraded,
                        SubmissionState::PendingManualGrading,
                        SubmissionState::Graded,
                        SubmissionState::Released
                    ]);

                    $submissionStatus = match ($submissionState) {
                        SubmissionState::InProgress => SubmissionStatus::Draft,
                        SubmissionState::Submitted => SubmissionStatus::Submitted,
                        SubmissionState::AutoGraded,
                        SubmissionState::PendingManualGrading,
                        SubmissionState::Graded,
                        SubmissionState::Released => SubmissionStatus::Graded,
                        default => SubmissionStatus::Draft,
                    };

                    $submittedAt = null;
                    if ($submissionState !== SubmissionState::InProgress) {
                        $submittedAt = now()->subDays(rand(1, 30));
                    }

                    $submission = Submission::create([
                        'assignment_id' => $assignment->id,
                        'user_id' => $user->id,
                        'status' => $submissionStatus,
                        'state' => $submissionState,
                        'submitted_at' => $submittedAt,
                        'attempt_number' => rand(1, 3),
                        'is_late' => fake()->boolean(15), // 15% are late
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $submissionCount++;

                    // Prepare answer data for later insertion
                    $answerData = [
                        'submission_id' => $submission->id,
                        'question_id' => $question->id,
                        'content' => null,
                        'selected_options' => null,
                        'file_paths' => null,
                        'score' => null,
                        'is_auto_graded' => false, // Set a default value
                        'feedback' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Add content based on question type
                    switch ($questionType) {
                        case QuestionType::MultipleChoice:
                        case QuestionType::Checkbox:
                            $selectedOptionIndex = rand(0, count($options) - 1);
                            $answerData['selected_options'] = json_encode([$options[$selectedOptionIndex]['id']]);
                            // These are auto-gradable question types, so set is_auto_graded to true
                            if ($answerData['score'] !== null) {
                                $answerData['is_auto_graded'] = true;
                            }
                            break;

                        case QuestionType::Essay:
                            $answerData['content'] = fake()->paragraph(rand(1, 5));
                            break;

                        case QuestionType::FileUpload:
                            $answerData['file_paths'] = json_encode([
                                fake()->word() . '.pdf',
                                fake()->word() . '.docx',
                            ]);
                            break;

                        default:
                            $answerData['content'] = fake()->paragraph();
                            break;
                    }

                    // Add scoring if the submission is graded
                    if (in_array($submissionState, [
                        SubmissionState::AutoGraded,
                        SubmissionState::Graded,
                        SubmissionState::Released
                    ])) {
                        $answerData['score'] = rand(0, (int)$question->max_score);
                        $answerData['is_auto_graded'] = fake()->boolean(70); // 70% auto-graded
                        $answerData['feedback'] = fake()->optional(0.6)->paragraph();
                    }

                    // Store in temporary array for batch insertion
                    $answers[] = $answerData;
                    $answerCount++;

                    // Batch insert answers periodically to avoid parameter limits
                    if (count($answers) >= 1000) {
                        Answer::insert($answers);
                        $answers = []; // Reset the array
                    }
                }
            }

            echo "Processed assignment: {$assignment->title}. Questions: $questionCount, Submissions: $submissionCount, Answers: $answerCount\n";
        }

        // Insert any remaining answers
        if (!empty($answers)) {
            Answer::insert($answers);
        }

        echo "✅ Question, option, answer, and submission seeding completed!\n";
        echo "Created $questionCount questions, $submissionCount submissions, and $answerCount answers\n";
    }
}