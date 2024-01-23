<?php

//se Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuotasController;
use App\Http\Controllers\RapiLoan\v1\RapiLoanController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['jwt.verify']], static function () {
    Route::get('/get-quotas-info', [QuotasController::class, 'getInfoQuotas']);
    Route::post('/get-revalue-quotas', [QuotasController::class, 'revalueQuotas']);
    Route::post('/update-revalue-quotas', [QuotasController::class, 'updateQuota']);
});

Route::post('/get-loan', [RapiLoanController::class, 'getCashin']);
Route::post('/pay-loan', [RapiLoanController::class, 'getCashinPay']);
