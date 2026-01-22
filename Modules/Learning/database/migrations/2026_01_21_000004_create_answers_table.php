<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');

            // Answer content for essay questions
            $table->text('content')->nullable();

            // Selected options for MCQ and Checkbox (JSON array)
            $table->json('selected_options')->nullable();

            // File paths for file upload questions (JSON array)
            $table->json('file_paths')->nullable();

            // Score for this answer (null if not graded yet)
            $table->decimal('score', 8, 2)->nullable();

            // Whether this answer was auto-graded
            $table->boolean('is_auto_graded')->default(false);

            // Feedback from instructor
            $table->text('feedback')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('submission_id', 'idx_answers_submission');
            $table->index('question_id', 'idx_answers_question');

            // Unique constraint: one answer per question per submission
            $table->unique(['submission_id', 'question_id'], 'uniq_answers_submission_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
