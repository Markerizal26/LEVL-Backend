<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jwt_refresh_tokens', function (Blueprint $table) {
            $table->string('device_id', 64)->nullable()->after('user_id');
            $table->foreignId('replaced_by')->nullable()->after('token')->constrained('jwt_refresh_tokens')->onDelete('set null');
            $table->timestamp('last_used_at')->nullable()->after('revoked_at');
            $table->timestamp('idle_expires_at')->nullable()->after('expires_at');
            $table->timestamp('absolute_expires_at')->nullable()->after('idle_expires_at');
            
            $table->index(['user_id', 'device_id', 'revoked_at']);
            $table->index(['user_id', 'replaced_by']);
        });
    }

    public function down(): void
    {
        Schema::table('jwt_refresh_tokens', function (Blueprint $table) {
            $table->dropForeign(['replaced_by']);
            $table->dropIndex(['user_id', 'device_id', 'revoked_at']);
            $table->dropIndex(['user_id', 'replaced_by']);
            $table->dropColumn(['device_id', 'replaced_by', 'last_used_at', 'idle_expires_at', 'absolute_expires_at']);
        });
    }
};

