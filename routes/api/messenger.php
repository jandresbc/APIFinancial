<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HablameController;
use App\Http\Controllers\AwsMessengerController;

// Messenger Services

Route::post('/send-message', [HablameController::class, 'sendMessage']);
Route::post('/send-mail-aws', [AwsMessengerController::class, 'sendMail']);
Route::post('/send-sms-aws', [AwsMessengerController::class, 'sendSms']);
