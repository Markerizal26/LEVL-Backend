<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->foreignId('prerequisite_id')->constrained('assignments')->onDelete('cascade');
            $table->timestamps();

            
            $table->unique(['assignment_id', 'prerequisite_id'], 'uniq_assignment_prerequisite');

            
            $table->index('assignment_id', 'idx_prereq_assignment');
            $table->index('prerequisite_id', 'idx_prereq_prerequisite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_prerequisites');
    }
};
