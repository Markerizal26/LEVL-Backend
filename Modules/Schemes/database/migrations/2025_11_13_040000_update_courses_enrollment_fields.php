<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'visibility')) {
                $table->dropColumn('visibility');
            }

            if (! Schema::hasColumn('courses', 'enrollment_type')) {
                $table->enum('enrollment_type', ['auto_accept', 'key_based', 'approval'])
                    ->default('auto_accept')
                    ->after('banner_path');
            }

            if (! Schema::hasColumn('courses', 'enrollment_key')) {
                $table->string('enrollment_key', 100)->nullable()->after('enrollment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'enrollment_type')) {
                $table->dropColumn('enrollment_type');
            }
            if (Schema::hasColumn('courses', 'enrollment_key')) {
                $table->dropColumn('enrollment_key');
            }
            if (! Schema::hasColumn('courses', 'visibility')) {
                $table->enum('visibility', ['public', 'private'])->default('public')->after('banner_path');
            }
        });
    }
};

