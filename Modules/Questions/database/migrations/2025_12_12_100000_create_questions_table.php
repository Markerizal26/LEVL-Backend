<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create("questions", function (Blueprint $table) {
      $table->id();
      $table->foreignId("category_id")->nullable()->constrained("categories")->nullOnDelete();
      $table->foreignId("created_by")->constrained("users")->cascadeOnDelete();
      $table->string("type");
      $table->string("difficulty")->default("medium");
      $table->text("question_text");
      $table->text("explanation")->nullable();
      $table->integer("points")->default(1);
      $table->json("tags")->nullable();
      $table->json("meta")->nullable();
      $table->integer("usage_count")->default(0);
      $table->timestamp("last_used_at")->nullable();
      $table->string("status")->default("active");
      $table->timestamps();
      $table->softDeletes();

      $table->index("type");
      $table->index("difficulty");
      $table->index("status");
      $table->index("created_by");
    });
  }

  public function down(): void
  {
    Schema::dropIfExists("questions");
  }
};
