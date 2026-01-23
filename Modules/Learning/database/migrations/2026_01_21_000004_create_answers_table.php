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
            $table->foreignId('question_id')->constrained('assignment_questions')->onDelete('cascade');

            
            $table->text('content')->nullable();

            
            $table->json('selected_options')->nullable();

            
            $table->json('file_paths')->nullable();

            
            $table->decimal('score', 8, 2)->nullable();

            
            $table->boolean('is_auto_graded')->default(false);

            
            $table->text('feedback')->nullable();

            $table->timestamps();

            
            $table->index('submission_id', 'idx_answers_submission');
            $table->index('question_id', 'idx_answers_question');

            
            $table->unique(['submission_id', 'question_id'], 'uniq_answers_submission_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
