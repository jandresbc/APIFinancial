<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EnBancaController;

Route::group(['middleware' => ['jwt.verify']], static function () {
    Route::post('/validate-number', [EnBancaController::class, 'validateNumber']);
    Route::get('/generate-file/{limit}', [EnBancaController::class, 'generateFile']);
});
