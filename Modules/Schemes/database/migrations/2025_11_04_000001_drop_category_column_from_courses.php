<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'category')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('courses') && ! Schema::hasColumn('courses', 'category')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->string('category', 100)->nullable()->after('level_tag');
            });
        }
    }
};
