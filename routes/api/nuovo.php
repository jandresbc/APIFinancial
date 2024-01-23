<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NuovoController;

Route::group(['middleware' => ['jwt.verify']], static function () {
    Route::post('/register', [NuovoController::class, 'register']);
    Route::post('/validate-phone', [NuovoController::class, 'validatePhone']);
    Route::post('/lock-phone', [NuovoController::class, 'lockPhone']);
    Route::post('/unlock-phone', [NuovoController::class, 'unlockPhone']);
});
