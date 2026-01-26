<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Submission;
use Modules\Learning\Models\Answer;
use Modules\Learning\Enums\QuestionType;

class QuestionAndAnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates comprehensive question and answer data:
     * - Adds questions to existing assignments
     * - Creates answers for existing submissions
     * - Ensures some submissions have ungraded answers for the grading queue
     */
    public function run(): void
    {
        echo "Seeding questions and answers...\n";

        // Check if any assignments exist to start with
        if (!Assignment::exists()) {
            echo "⚠️  No assignments found. Skipping question and answer seeding.\n";
            return;
        }

        // Check if any submissions exist
        if (!Submission::exists()) {
            echo "⚠️  No submissions found. Skipping answer seeding.\n";
            return;
        }

        $questionCount = 0;
        $answerCount = 0;

        // Process assignments in chunks to create questions
        Assignment::withCount('questions')->chunkById(1000, function ($assignments) use (&$questionCount) {
            $questions = [];

            foreach ($assignments as $assignment) {
                // Add 3-8 questions per assignment
                $numQuestions = rand(3, 8);

                for ($i = 0; $i < $numQuestions; $i++) {
                    $questionType = fake()->randomElement(['essay', 'multiple_choice', 'short_answer', 'file_upload']);
                    
                    $questionData = [
                        'assignment_id' => $assignment->id,
                        'type' => $questionType,
                        'content' => fake()->sentence(15),
                        'weight' => fake()->randomFloat(2, 1, 5),
                        'order' => $i + 1,
                        'max_score' => fake()->randomElement([10, 20, 25, 50, 100]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Initialize all possible fields to avoid column mismatch
                    $questionData['options'] = null;
                    $questionData['answer_key'] = null;
                    $questionData['max_file_size'] = null;
                    $questionData['allowed_file_types'] = null;
                    $questionData['allow_multiple_files'] = false;

                    // Add type-specific fields
                    switch ($questionType) {
                        case 'multiple_choice':
                            $questionData['options'] = json_encode([
                                ['id' => fake()->uuid(), 'label' => fake()->sentence(3)],
                                ['id' => fake()->uuid(), 'label' => fake()->sentence(3)],
                                ['id' => fake()->uuid(), 'label' => fake()->sentence(3)],
                                ['id' => fake()->uuid(), 'label' => fake()->sentence(3)],
                            ]);
                            $questionData['answer_key'] = json_encode(['correct_option' => 0]);
                            break;

                        case 'short_answer':
                            $questionData['answer_key'] = json_encode(['acceptable_answers' => [
                                fake()->word(),
                                fake()->word(),
                                fake()->word(),
                            ]]);
                            break;

                        case 'file_upload':
                            $questionData['max_file_size'] = 10000000;
                            $questionData['allowed_file_types'] = json_encode(['pdf', 'docx', 'txt', 'png', 'jpg']);
                            $questionData['allow_multiple_files'] = true;
                            break;

                        case 'essay':
                        default:
                            // Essay doesn't need special fields beyond defaults
                            break;
                    }

                    $questions[] = $questionData;
                    $questionCount++;
                }
            }

            // Batch insert questions in smaller chunks to avoid parameter limits
            if (!empty($questions)) {
                foreach (array_chunk($questions, 1000) as $chunk) {
                    \Illuminate\Support\Facades\DB::table('assignment_questions')->insert($chunk);
                }
            }

            echo "Processed chunk. Questions created: $questionCount\n";
        });

        // Process submissions in chunks to create answers
        Submission::with('assignment.questions')->chunkById(1000, function ($submissions) use (&$answerCount) {
            $answers = [];

            foreach ($submissions as $submission) {
                // Only create answers for submissions that have assignments with questions
                if (!$submission->assignment || $submission->assignment->questions_count == 0) {
                    continue;
                }

                // Create an answer for each question in the assignment
                foreach ($submission->assignment->questions as $question) {
                    // Skip some answers to simulate incomplete submissions (20% chance to skip)
                    if (rand(1, 100) <= 20) {
                        continue;
                    }

                    $answerData = [
                        'submission_id' => $submission->id,
                        'question_id' => $question->id,
                        'score' => null, // Initially null, meaning not graded
                        'is_auto_graded' => false, // Not auto-graded initially
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Add content based on question type
                    switch ($question->type) {
                        case 'multiple_choice':
                            $options = json_decode($question->options, true);
                            if ($options) {
                                $answerData['selected_options'] = json_encode([
                                    $options[rand(0, count($options)-1)]['id']
                                ]);
                            }
                            break;
                        
                        case 'short_answer':
                        case 'essay':
                            $answerData['content'] = fake()->paragraph(rand(2, 5));
                            break;
                            
                        case 'file_upload':
                            $answerData['file_paths'] = json_encode([
                                fake()->sha256() . '.pdf',
                                fake()->sha256() . '.docx',
                            ]);
                            $answerData['file_metadata'] = json_encode([
                                'total_size' => fake()->numberBetween(100000, 5000000),
                                'file_count' => 2,
                            ]);
                            break;
                    }

                    $answers[] = $answerData;
                    $answerCount++;
                }

                // Batch insert answers when we have enough
                if (count($answers) >= 1000) {
                    \Illuminate\Support\Facades\DB::table('answers')->insertOrIgnore($answers);
                    $answers = [];
                }
            }

            // Insert remaining answers in smaller chunks to avoid parameter limits
            if (!empty($answers)) {
                foreach (array_chunk($answers, 1000) as $chunk) {
                    \Illuminate\Support\Facades\DB::table('answers')->insertOrIgnore($chunk);
                }
            }

            echo "Processed chunk. Answers created: $answerCount\n";
        });

        echo "✅ Question and answer seeding completed!\n";
        echo "Created $questionCount questions with $answerCount answers\n";
        
        // Update some submissions to have the correct state for grading
        $this->updateSubmissionStates();
    }

    private function updateSubmissionStates(): void
    {
        echo "Updating submission states for grading queue...\n";

        // Update submissions that have answers but are not yet in the correct state for grading
        $updated = \DB::table('submissions')
            ->join('answers', 'submissions.id', '=', 'answers.submission_id')
            ->where('submissions.status', 'submitted')
            ->whereNull('submissions.state')
            ->whereNull('answers.score') // Answers without scores need grading
            ->distinct('submissions.id')
            ->limit(100) // Update only 100 to avoid overloading
            ->update(['submissions.state' => 'pending_manual_grading']);

        echo "✅ Updated $updated submissions to pending_manual_grading state\n";
    }
}