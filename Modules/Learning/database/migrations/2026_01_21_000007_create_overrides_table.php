<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('grantor_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); 
            $table->text('reason'); 
            $table->json('value')->nullable(); 
            $table->timestamp('granted_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            
            $table->index('assignment_id', 'idx_overrides_assignment');
            $table->index('student_id', 'idx_overrides_student');
            $table->index('grantor_id', 'idx_overrides_grantor');
            $table->index('type', 'idx_overrides_type');
            $table->index('expires_at', 'idx_overrides_expires');

            
            $table->index(['assignment_id', 'student_id', 'type'], 'idx_overrides_assignment_student_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overrides');
    }
};
