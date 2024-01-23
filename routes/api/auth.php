<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register/{username}/{password}', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'authenticate']);
Route::post('/v2/login', [AuthController::class, 'authenticateV2']);
Route::post('/user', [AuthController::class, 'getAuthenticatedUser']);
Route::post('/refresh', [AuthController::class, 'refreshToken']);
