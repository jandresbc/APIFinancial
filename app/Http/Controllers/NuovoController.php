<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Http\Integrations\Nuovo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class NuovoController extends Controller
{
    public function lockPhone(Request $request):JsonResponse
    {
        try {
            $validation = Validator::make($request->all(), [
                'device_id' => 'required'
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'message' => 'Missing data please rectify the information sent.',
                    'error' => $validation->errors()->getMessages(),
                    'status' => false,
                    'code' => 400
                ], 400);
            }
            $nuovo = new Nuovo();

            $data = $request->all();

            $response = $nuovo->lockPhone($data['device_id']);
            if (!$response['status']) {
                return response()->json($response, $response['code']);
            }

            return response()->json($response);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ],500);
        }
    }

    public function unlockPhone(Request $request):JsonResponse
    {
        try {
            $validation = Validator::make($request->all(), [
                'device_id' => 'required'
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'message' => 'Missing data please rectify the information sent.',
                    'error' => $validation->errors()->getMessages(),
                    'status' => false,
                    'code' => 400
                ], 400);
            }
            $nuovo = new Nuovo();

            $data = $request->all();

            $response = $nuovo->unlockPhone($data['device_id'], $data['lock_date'] ?? null);
            if (!$response['status']) {
                return response()->json($response, $response['code']);
            }

            return response()->json($response);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ],500);
        }
    }

    public function validatePhone(Request $request):JsonResponse
    {
        try {
            $validation = Validator::make($request->all(), [
                'imei' => 'required'
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'message' => 'Missing data please rectify the information sent.',
                    'error' => $validation->errors()->getMessages(),
                    'status' => false,
                    'code' => 400
                ], 400);
            }
            $nuovo = new Nuovo();

            $data = $request->all();

            $response = $nuovo->validatePhone($data['imei']);
            if (!$response['status']) {
                return response()->json($response, $response['code']);
            }

            return response()->json($response);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ],500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request):JsonResponse
    {
        try {
            $validation = Validator::make($request->all(), [
                'imei' => 'required'
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'message' => 'Missing data please rectify the information sent.',
                    'error' => $validation->errors()->getMessages(),
                    'status' => false,
                    'code' => 400
                ], 400);
            }
            $nuovo = new Nuovo();

            $data = $request->all();

            $response = $nuovo->register($data['imei']);
            if (!$response['status']) {
                return response()->json($response, $response['code']);
            }

            return response()->json($response);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ],500);
        }
    }
}
