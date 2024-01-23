<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TruoraController;

// Truora service

Route::get('/get-validation-link', [TruoraController::class, 'getValidationLink']);
Route::get('/{process_id}/get-status-validation', [TruoraController::class, 'getStatusValidation']);
