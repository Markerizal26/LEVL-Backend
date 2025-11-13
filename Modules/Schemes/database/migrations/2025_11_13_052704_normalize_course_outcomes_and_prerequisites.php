<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->text('outcome_text');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['course_id', 'order']);
        });

        Schema::create('course_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('prerequisite_course_id')->constrained('courses')->onDelete('cascade');
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['course_id', 'prerequisite_course_id']);
            $table->index('course_id');
        });

        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (Schema::hasColumn('courses', 'outcomes_json')) {
                    $table->dropColumn('outcomes_json');
                }
                if (Schema::hasColumn('courses', 'prereq_text')) {
                    $table->dropColumn('prereq_text');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (!Schema::hasColumn('courses', 'outcomes_json')) {
                    $table->json('outcomes_json')->nullable()->after('tags_json');
                }
                if (!Schema::hasColumn('courses', 'prereq_text')) {
                    $table->text('prereq_text')->nullable()->after('outcomes_json');
                }
            });
        }

        Schema::dropIfExists('course_prerequisites');
        Schema::dropIfExists('course_outcomes');
    }
};
