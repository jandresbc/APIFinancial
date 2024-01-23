<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\MotoSafeService;
use Exception;
use Illuminate\Http\JsonResponse;

class MotoSafeController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \JsonException
     */
    public function createOrder (Request $request): JsonResponse
    {
        $motoSafe = new MotoSafeService();

        try {

            $data = $request->all();
            $response = $motoSafe->createOrder($data);

            if (!$response['status']) {
                return response()->json($response, $response['code']);
            }

            return response()->json($response);
        } catch (Exception $e) {
            $motoSafe->createLog($e->getMessage(),'error', [
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
            ]);
            return response()->json([
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \JsonException
     */
    public function createOrderOld (Request $request): JsonResponse
    {
        $motoSafe = new MotoSafeService();

        try {

            $data = $request->all();
            $response = $motoSafe->createOrder($data);

            if (!$response['status']) {
                return response()->json($response, $response['code']);
            }

            return response()->json($response);
        } catch (Exception $e) {
            $motoSafe->createLog($e->getMessage(),'error', [
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
            ]);
            return response()->json([
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }

    /**
     * @param $imei
     * @return JsonResponse
     * @throws \JsonException
     */
    public function getOrderByImei ($imei): JsonResponse
    {
        $motoSafe = new MotoSafeService();

        try {
            $response = $motoSafe->getOrderByImei($imei);

            if (!$response['status']) {
                return response()->json($response, $response['code']);
            }

            return response()->json($response);
        } catch (Exception $e) {
            $motoSafe->createLog($e->getMessage(),'error', [
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
            ]);
            return response()->json([
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }

    /**
     * @return JsonResponse
     * @throws \JsonException
     */
    public function getOrders (): JsonResponse
    {
        $motoSafe = new MotoSafeService();

        try {
            $response = $motoSafe->getOrders();

            if (!$response['status']) {
                return response()->json($response, $response['code']);
            }

            return response()->json($response);
        } catch (Exception $e) {
            $motoSafe->createLog($e->getMessage(),'error', [
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
            ]);
            return response()->json([
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }
}
