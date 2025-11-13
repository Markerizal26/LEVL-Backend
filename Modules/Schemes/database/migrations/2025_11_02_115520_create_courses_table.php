<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('slug', 100)->unique();
            $table->string('title', 255);
            $table->text('short_desc')->nullable();
            $table->enum('type', ['okupasi', 'kluster'])->default('okupasi');
            $table->enum('level_tag', ['dasar', 'menengah', 'mahir'])->default('dasar');
            $table->string('category', 100)->nullable();
            $table->json('tags_json')->nullable();
            $table->json('outcomes_json')->nullable();
            $table->text('prereq_text')->nullable();
            // $table->integer('duration_estimate')->nullable(); (Notes BE:Ganti di Response menampilkan by logic)
            $table->string('thumbnail_path', 255)->nullable();
            $table->string('banner_path', 255)->nullable();
            $table->enum('enrollment_type', ['auto_accept', 'key_based', 'approval'])->default('auto_accept');
            $table->string('enrollment_key', 100)->nullable();
            $table->enum('progression_mode', ['sequential', 'free'])->default('sequential');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status']);
            $table->index(['enrollment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
