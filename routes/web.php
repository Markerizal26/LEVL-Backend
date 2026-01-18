<?php

use Illuminate\Support\Facades\Route;

Route::get("/", function () {
  return view("welcome");
});

require __DIR__ . "/test-browser.php";

Route::get('/dev/octane-check', [App\Http\Controllers\DevController::class, 'checkOctane']);
Route::get('/dev/benchmark', [App\Http\Controllers\DevController::class, 'benchmarkView']);
Route::get('/dev/benchmark-api', [App\Http\Controllers\DevController::class, 'benchmarkApi'])
    ->middleware('api');  


