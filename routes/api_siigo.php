<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\siigo\v2\TokenController;
use App\Http\Controllers\siigo\v2\InvoiceController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::get('/greeting', function () {
    return 'Hello World';
});

Route::post('v2/auth', [TokenController::class, 'index']);

Route::controller(InvoiceController::class)->group(function () {
    Route::get('v2/invoices', 'index');
    Route::get('v2/invoices/{id}', 'show');
    Route::get('v2/invoices/download/{id}', 'download');
    Route::get('v2/invoices/download/number/{number}', 'downloadByNumber');
    Route::post('v2/invoices', 'create');
});