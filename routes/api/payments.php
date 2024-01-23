<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentsController;

Route::group(['middleware' => ['jwt.verify']], function () {
    Route::post('/payment-confirmation', [PaymentsController::class, 'paymentConfirmation']);
});

Route::get('/search-payment', [PaymentsController::class, 'searchPayment']);
Route::post('/create-payment', [PaymentsController::class, 'createPayment']);
Route::post('/create-new-payment', [PaymentsController::class, 'createNewPayment']);
Route::post('/update-payment', [PaymentsController::class, 'updatePayment']);

// paymentez
Route::post('/paymentez/payment-method', [PaymentsController:: class, 'PaymentMethod']);
