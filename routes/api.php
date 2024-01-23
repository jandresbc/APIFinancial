<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\LockUnlockController;
use App\Http\Controllers\OmbuCcPrestamosController;
use App\Http\Controllers\ContactFlowsController;
use App\Http\Controllers\UrlShortenerController;
use App\Http\Controllers\WompiController;
use App\Http\Controllers\EmailageRiskController;
use App\Http\Controllers\MessageController;

use App\Http\Controllers\SoftLockController;

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

Route::get('/', static function() {
    return 'Creditek API V1';
});

Route::get('/testtt', [SoftLockController::class, 'test']);

Route::get('/lock-unlock', [LockUnlockController::class, 'lockUnlock']);

// check credit user by document number
Route::group(['middleware' => 'jwt.verify'], static function() {
    Route::get('/get-credit-user/{nro_doc}', [OmbuCcPrestamosController::class, 'checkCreditUser']);
});

// Wompi Service
Route::get('/v1/transactions/{transaction_id}', [WompiController::class, 'getTransaction']);
Route::post('/wompi/payments/links', [WompiController::class, 'paymentLink']);
Route::post('/wompi/payments/events', [WompiController::class, 'paymentEvent']);
//->middleware('event');

//url shortener
Route::post('/post-url-shorter', [UrlShortenerController::class, 'urlShorter']);
Route::post('/getUrl', [UrlShortenerController::class, 'redirect']);

//Redirect url
Route::get('/code/{url}', static function ($url) {
    $request = new Request();
    $request->new_url = $url;
    $redirect = new urlShortenerController();
    $redirect->redirect($request->new_url);

});


// Contact Flows
Route::post('/flows',[ContactFlowsController::class, 'contactFlows']);

// Emailage Services

Route::prefix('emailage')->group(function() {
    Route::group(['middleware' => 'jwt.verify'], static function() {
        Route::post('/risks/v2/band', [EmailageRiskController::class, 'checkRiskBandV2']);
        Route::get('/risks/band', [EmailageRiskController::class, 'checkRiskBand']);
        Route::get('/risks/validate', [EmailageRiskController::class, 'clientRiskLevel']);
        Route::post('/risks/fraud', [EmailageRiskController::class, 'clientRiskFraud']);
    });
});

Route::post('/messagewhatsapp',[MessageController::class, 'postMessage']);
