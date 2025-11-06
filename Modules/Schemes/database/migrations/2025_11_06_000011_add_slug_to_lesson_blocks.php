<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lesson_blocks', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('lesson_id');
        });

        // Backfill slug with UUIDs for existing rows
        $blocks = DB::table('lesson_blocks')->select('id')->get();
        foreach ($blocks as $b) {
            DB::table('lesson_blocks')->where('id', $b->id)->update(['slug' => (string) \Illuminate\Support\Str::uuid()]);
        }

        Schema::table('lesson_blocks', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique(['lesson_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('lesson_blocks', function (Blueprint $table) {
            $table->dropUnique(['lesson_id', 'slug']);
            $table->dropColumn('slug');
        });
    }
};


