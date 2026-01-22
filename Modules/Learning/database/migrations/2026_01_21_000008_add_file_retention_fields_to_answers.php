<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            // Timestamp when files were marked as expired
            $table->timestamp('files_expired_at')->nullable()->after('file_paths');

            // File metadata to preserve after deletion
            // Stores original file info: name, size, type, upload date
            $table->json('file_metadata')->nullable()->after('files_expired_at');
        });

        // Add index for efficient querying of expired files
        Schema::table('answers', function (Blueprint $table) {
            $table->index('files_expired_at', 'idx_answers_files_expired_at');
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropIndex('idx_answers_files_expired_at');
            $table->dropColumn(['files_expired_at', 'file_metadata']);
        });
    }
};
