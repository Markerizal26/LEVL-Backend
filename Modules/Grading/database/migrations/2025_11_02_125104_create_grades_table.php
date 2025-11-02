<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['assignment', 'attempt']);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('score', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2)->default(100);
            $table->text('feedback')->nullable();
            $table->enum('status', ['pending', 'graded', 'reviewed'])->default('graded');
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
