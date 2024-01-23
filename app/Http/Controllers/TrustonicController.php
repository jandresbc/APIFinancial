<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Http\Services\TrustonicService;

class
TrustonicController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function register (Request $request): JsonResponse
    {
        try {
            $trustonic = new TrustonicService();
            $response = $trustonic->register($request->all());

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
     * @throws GuzzleException
     */
    public function update (Request $request): JsonResponse
    {
        try {
            $trustonic = new TrustonicService();
            $response = $trustonic->update($request->all());

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
     * @throws GuzzleException
     */
    public function getPin (Request $request): JsonResponse
    {
        try {
            $trustonic = new TrustonicService();
            $response = $trustonic->getPin($request->all());

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
     * @throws GuzzleException
     */
    public function delete (Request $request): JsonResponse
    {
        try {
            $trustonic = new TrustonicService();
            $response = $trustonic->delete($request->all());

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
     * @param $imei
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function getStatus ($imei): JsonResponse
    {
        try {
            $trustonic = new TrustonicService();
            $response = $trustonic->getStatus($imei);

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
