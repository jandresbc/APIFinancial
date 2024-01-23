<?php

namespace App\Http\Controllers;

use Aws\Exception\AwsException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Integrations\AwsMessenger;
use Exception;

class AwsMessengerController extends Controller
{
    public function sendMail (Request $request): JsonResponse
    {
        try {
            $data = Validator::make($request->all(), [
                'email' => 'required|email',
                'message' => 'required|string',
                'matter' => 'required|string'
            ]);
            if ($data->fails()) {
                return response()->json([
                    'message' => 'Missing data please rectify the information sent.',
                    'error_fields' => $data->errors()->getMessages(),
                    'code' => 400,
                    'status' => false
                ], 400);
            }
            AwsMessenger::sendMail($request);

            return response()->json([
                'message' => 'mail sending success',
                'data' => [
                    'email' => $request->email,
                    'matter' => $request->matter,
                    'messageMail' => $request->message
                ],
                'status' => true,
                'code' => 200
            ], 200);
        } catch (AwsException|Exception $e) {
            return response()->json([
                'message' => 'An error has occurred in our system, please try again later',
                'messageError' => $e->getMessage(),
                'code' => 500,
                'status' => false
            ],500);
        }
    }

    public function sendSms (Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'phone' => 'required|integer',
            'message' => 'required|string|max:160',
            'country_code' => 'required'
        ]);

        if ($data->fails()) {
            return response()->json([
                'message' => 'Missing data please rectify the information sent.',
                'error_fields' => $data->errors()->getMessages(),
                'code' => 400,
                'status' => false
            ], 400);
        }
        try {
            $result = AwsMessenger::sendSMS($request);
            return response()->json([
                'message' => 'success, sms send to number',
                'data' => [
                    'result' => $result
                ],
                'status' => true,
                'code' => 200
            ],200);
        } catch (AwsException $e) {
            return response()->json([
                'message' => 'An error has occurred in our system, please try again later',
                'messageError' => $e->getMessage(),
                'code' => 500,
                'status' => false
            ],500);
        }

    }
}
