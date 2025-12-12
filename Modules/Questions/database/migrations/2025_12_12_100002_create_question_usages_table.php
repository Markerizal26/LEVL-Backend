<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create("question_usages", function (Blueprint $table) {
      $table->id();
      $table->foreignId("question_id")->constrained("questions")->cascadeOnDelete();
      $table->morphs("usable");
      $table->foreignId("used_by")->constrained("users")->cascadeOnDelete();
      $table->timestamps();

      $table->index(["question_id", "usable_type", "usable_id"]);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists("question_usages");
  }
};
