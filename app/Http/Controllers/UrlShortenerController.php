<?php

namespace App\Http\Controllers;

use App\Http\Services\urlShortnerServices;
use Illuminate\Http\Request;
use Exception;
use App\Models\CrLog\UrlShortLog;
use App\Models\CrCore\UrlShort;
use Illuminate\Http\JsonResponse;

class UrlShortenerController extends Controller
{
    public function urlShorter(Request $request): JsonResponse
    {
        try{
            $logCreate = new UrlShortLog();
            $urlShortener = new urlShortnerServices();
            $response = $urlShortener->generateURL($request);
            $logCreate->type = 'success';
            $logCreate->message = 'this message is send successful';
            $logCreate->new_url = $response['new_url'];
            $logCreate->created_at = date("Y-m-d H:i:s");
            $logCreate->updated_at = date("Y-m-d H:i:s");
            $logCreate->save();

            if (!$response['status']) {
                return response()->json($response, $response['code']);
            }

            return response()->json($response);
        }catch(Exception $e){
            return response()->json([
                'message' => 'A system error has occurred, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ]);
        }
    }

    public function redirect($request){
        try{
            $urlShort = new UrlShort();
            $link = $urlShort->where('url_key', $request)->get();
            $token = $link[0]->token + 1;
            $urlShort->where('url_key', $request)->update(['token' => $token]);
            header("Location: ".$link[0]->to_url);
            die();
        }catch(Exception $e){
            return response()->json([
                'message' => 'Error al procesar la información, volverlo a intentarlo',
                'errorMessage' => $e->getMessage(),
                'errorCode' => $e->getCode(),
                'code' => 500,
                'status' => false
            ]);
        }
    }
}
