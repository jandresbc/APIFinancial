<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MotoSafeController;

Route::get('/get-order-by-imei/{imei}', [MotoSafeController::class, 'getOrderByImei']);
Route::get('/get-orders', [MotoSafeController::class, 'getOrders']);
Route::post('/create-order', [MotoSafeController::class, 'createOrder']);
Route::post('/create-order-old', [MotoSafeController::class, 'createOrderOld']);
