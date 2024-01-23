<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RequestsController;

Route::get('/request-list', [RequestsController::class, 'index']);
Route::put('/update-step', [RequestsController::class, 'skipStep']);
Route::put('/cancel-request', [RequestsController::class, 'cancelRequest']);
Route::put('/update-imei', [RequestsController::class, 'updateImei']);
