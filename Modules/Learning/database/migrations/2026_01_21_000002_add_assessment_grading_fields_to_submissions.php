<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            
            $table->dropUnique(['assignment_id', 'user_id']);

            
            $table->decimal('score', 8, 2)->nullable()->after('status');

            
            $table->json('question_set')->nullable()->after('score');

            
            $table->index(['assignment_id', 'user_id', 'attempt_number'], 'idx_submissions_assignment_user_attempt');

            
            $table->index(['status', 'submitted_at'], 'idx_submissions_status_submitted');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex('idx_submissions_assignment_user_attempt');
            $table->dropIndex('idx_submissions_status_submitted');
            $table->dropColumn(['score', 'question_set']);

            
            $table->unique(['assignment_id', 'user_id']);
        });
    }
};
