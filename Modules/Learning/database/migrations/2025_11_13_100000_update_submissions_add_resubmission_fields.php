<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->integer('attempt_number')->default(1)->after('enrollment_id');
            $table->boolean('is_late')->default(false)->after('status');
            $table->boolean('is_resubmission')->default(false)->after('is_late');
            $table->foreignId('previous_submission_id')->nullable()->after('is_resubmission')
                ->constrained('submissions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['previous_submission_id']);
            $table->dropColumn(['attempt_number', 'is_late', 'is_resubmission', 'previous_submission_id']);
        });
    }
};

