<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\SelfManagerService;

class SelfManagerController extends Controller
{

    //authentication two-factor
    public function auth(Request $request)
    {
        $service = new SelfManagerService();

        $auth = $service->authenticate($request);

        return response()->json([
            "status" => true,
            "code" => 200,
            "message" => "Success",
            "data"=> $auth
        ]);
    }

    public function validateAuth(Request $request)
    {
        $service = new SelfManagerService();

        $validate = $service->validateAuth($request);

        return response()->json([
            "status" => true,
            "code" => 200,
            "message" => "Success",
            "data"=> $validate
        ]);
    }

    public function getInfoClient(Request $request)
    {
        $service = new SelfManagerService();

        $response = $service->getInfoClient($request);

        return response()->json([
            "status" => true,
            "code" => 200,
            "message" => "Success",
            "data"=> $response
        ]);
    }
}