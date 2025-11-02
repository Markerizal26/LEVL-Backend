<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grading_rubrics', function (Blueprint $table) {
            $table->id();
            $table->enum('scope_type', ['exercise', 'assignment']);
            $table->unsignedBigInteger('scope_id');
            $table->string('criteria', 255);
            $table->text('description')->nullable();
            $table->integer('max_score')->default(10);
            $table->integer('weight')->default(1); 
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_rubrics');
    }
};
