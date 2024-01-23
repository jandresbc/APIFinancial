<?php

use App\Http\Controllers\ContactFlowsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoanBotController;

// WhatsApp Service
Route::post('/time-whatssap', [ContactFlowsController::class, 'dateMessage']);
Route::group(['middleware' => ['jwt.verify']], static function () {
    Route::post('/loan-verify', [LoanBotController::class, 'verifyStatus']);
});
Route::post('/reception-message', [LoanBotController::class, 'getMessageWhatsapp']);
Route::post('/trigger-message', [LoanBotController::class, 'getTriggerMessage']);
Route::post('/verify-seller', [LoanBotController::class, 'verifySeller']);
Route::post('/reset-process', [LoanBotController::class, 'resetProcess']);

//Temporal bot
Route::post('/hook', [LoanBotController::class, 'getMessage']);