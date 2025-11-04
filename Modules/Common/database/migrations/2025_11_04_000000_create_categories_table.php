<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('value', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (! Schema::hasColumn('courses', 'category_id')) {
                    $table->foreignId('category_id')->nullable()->after('category')->constrained('categories')->nullOnDelete();
                    $table->index('category_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'category_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('category_id');
            });
        }
        Schema::dropIfExists('categories');
    }
};
