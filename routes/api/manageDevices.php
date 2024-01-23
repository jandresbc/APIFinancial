<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ManageDevicesController;

Route::post('/register', [ManageDevicesController::class, 'EnrolledDevice']);
Route::post('/validation', [ManageDevicesController::class, 'ValidationDevice']);
