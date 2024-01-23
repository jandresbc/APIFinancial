<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/obtain-product', [ProductController::class, 'getProduct']);
