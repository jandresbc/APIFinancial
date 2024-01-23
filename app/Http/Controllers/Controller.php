<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function Ok ($message, $data): JsonResponse
    {
        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => $data,
            'message' => $message
        ]);
    }

    public function Error ($message, $data, $code): JsonResponse
    {
        return response()->json([
            'status' => false,
            'code' => $code,
            'data' => $data,
            'message' => $message
        ], $code);
    }

    public function respond($data, $msg = null)
    {
        return ResponseBuilder::asSuccess()->withData($data)->withMessage($msg)->build();
    }

    public function respondWithMessage($msg)
    {
        return ResponseBuilder::asSuccess()->withMessage($msg)->build();
    }

    public function respondWithSuccess($data, $http_code)
    {
        return ResponseBuilder::asSuccess()->withData($data)->withHttpCode($http_code)->build();
    }

    public function respondWithError($api_code, $http_code, $msg = null, $data = [])
    {
        if(isset($msg) && !empty($msg))
        {
            return ResponseBuilder::asError($api_code)->withHttpCode($http_code)->withMessage($msg)->withData($data)->build();
        }
        return ResponseBuilder::asError($api_code)->withHttpCode($http_code)->withData($data)->build();
    }

    public function respondBadRequest($api_code)
    {
        return $this->respondWithError($api_code, 400);
    }

    public function respondUnAuthorizedRequest($api_code)
    {
        return $this->respondWithError($api_code, 401);
    }

    public function respondNotFound($api_code)
    {
        return $this->respondWithError($api_code, 404);
    }

    public function errorWithMessage($api_code)
    {
        return $this->errorWithMessage($api_code, 404);
    }
}
