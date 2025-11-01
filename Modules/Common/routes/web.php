<?php

use Illuminate\Support\Facades\Route;
use Modules\Common\Http\Controllers\CommonController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('commons', CommonController::class)->names('common');
});
