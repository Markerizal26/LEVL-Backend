<?php

use Illuminate\Support\Facades\Route;
use Modules\Questions\Http\Controllers\QuestionsController;

Route::middleware(["auth:api"])
  ->prefix("v1")
  ->group(function () {
    Route::get("questions/random", [QuestionsController::class, "random"])->name(
      "questions.random",
    );
    Route::apiResource("questions", QuestionsController::class)->names("questions");
  });
