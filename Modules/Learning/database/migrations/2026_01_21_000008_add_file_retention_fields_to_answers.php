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
            
            $table->timestamp('files_expired_at')->nullable()->after('file_paths');

            
            
            $table->json('file_metadata')->nullable()->after('files_expired_at');
        });

        
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
