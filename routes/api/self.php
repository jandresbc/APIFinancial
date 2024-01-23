<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SelfManagerController;

Route::group(['middleware' => ['jwt.verify']], static function () {
    Route::post('/getdata', [SelfManagerController::class, 'getInfoClient']);
    Route::post('/auth', [SelfManagerController::class, 'auth']);
    Route::post('/validateAuth', [SelfManagerController::class, 'validateAuth']);
});

