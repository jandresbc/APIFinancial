<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MotorRiesgoController;


    Route::post('/validate', [MotorRiesgoController::class, 'getRisk']);

    Route::get('/getStatus', [MotorRiesgoController::class, 'getStatus']);

