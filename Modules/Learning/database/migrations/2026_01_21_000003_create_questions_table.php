<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');

            // Question type: multiple_choice, checkbox, essay, file_upload
            $table->string('type', 30);

            // Question content (supports HTML)
            $table->text('content');

            // Options for MCQ and Checkbox (JSON array)
            $table->json('options')->nullable();

            // Answer key for auto-grading (JSON)
            $table->json('answer_key')->nullable();

            // Weight for scoring calculation
            $table->decimal('weight', 8, 2)->default(1.00);

            // Display order
            $table->integer('order')->default(0);

            // Max score for this question
            $table->decimal('max_score', 8, 2)->default(100.00);

            // File upload settings
            $table->integer('max_file_size')->nullable(); // in bytes
            $table->json('allowed_file_types')->nullable();
            $table->boolean('allow_multiple_files')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('assignment_id', 'idx_questions_assignment');
            $table->index('type', 'idx_questions_type');
            $table->index('order', 'idx_questions_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
