<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->foreignId('submission_id')->nullable()->after('source_id')
                ->constrained('submissions')->onDelete('cascade');

            $table->decimal('original_score', 8, 2)->nullable()->after('score');

            $table->boolean('is_override')->default(false)->after('original_score');
            $table->text('override_reason')->nullable()->after('is_override');

            $table->boolean('is_draft')->default(false)->after('override_reason');

            $table->timestamp('released_at')->nullable()->after('graded_at');

            $table->index('submission_id', 'idx_grades_submission');

            $table->index('released_at', 'idx_grades_released');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex('idx_grades_submission');
            $table->dropIndex('idx_grades_released');
            $table->dropForeign(['submission_id']);
            $table->dropColumn([
                'submission_id',
                'original_score',
                'is_override',
                'override_reason',
                'is_draft',
                'released_at',
            ]);
        });
    }
};
