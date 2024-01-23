<?php

use App\Http\Controllers\TrustonicController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [TrustonicController::class, 'register']);
Route::post('/update', [TrustonicController::class, 'update']);
Route::post('/get-pin', [TrustonicController::class, 'getPin']);
Route::post('/delete', [TrustonicController::class, 'delete']);
Route::get('/get-status/{imei}', [TrustonicController::class, 'getStatus']);
