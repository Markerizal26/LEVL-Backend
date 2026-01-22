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

            
            $table->string('type', 30);

            
            $table->text('content');

            
            $table->json('options')->nullable();

            
            $table->json('answer_key')->nullable();

            
            $table->decimal('weight', 8, 2)->default(1.00);

            
            $table->integer('order')->default(0);

            
            $table->decimal('max_score', 8, 2)->default(100.00);

            
            $table->integer('max_file_size')->nullable(); 
            $table->json('allowed_file_types')->nullable();
            $table->boolean('allow_multiple_files')->default(false);

            $table->timestamps();

            
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
