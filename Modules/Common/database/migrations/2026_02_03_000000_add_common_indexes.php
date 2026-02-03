<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->index('name', 'idx_categories_name');
            $table->index('status', 'idx_categories_status');
            $table->index('created_at', 'idx_categories_created_at');
        });

        Schema::table('level_configs', function (Blueprint $table) {
            $table->index('name', 'idx_level_configs_name');
            $table->index('xp_required', 'idx_level_configs_xp_required');
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->index('type', 'idx_system_settings_type');
            $table->index('created_at', 'idx_system_settings_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_name');
            $table->dropIndex('idx_categories_status');
            $table->dropIndex('idx_categories_created_at');
        });

        Schema::table('level_configs', function (Blueprint $table) {
            $table->dropIndex('idx_level_configs_name');
            $table->dropIndex('idx_level_configs_xp_required');
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropIndex('idx_system_settings_type');
            $table->dropIndex('idx_system_settings_created_at');
        });
    }
};
