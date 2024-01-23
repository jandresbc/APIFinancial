<?php

namespace App\Http\Controllers;

use App\Exports\EnBancaExport;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Http\Services\EnBancaService;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EnBancaController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function validateNumber (Request $request):JsonResponse
    {
        $verifyData = Validator::make($request->all(), [
            'phone' => 'required',
            'document' => 'required',
        ]);

        if ($verifyData->fails()) {
            return response()->json([
                'message' => 'Missing data please rectify the information sent.',
                'error_fields' => $verifyData->errors()->getMessages(),
                'code' => 400,
                'status' => false
            ], 400);
        }
        try {
            $enBanca = new EnBancaService();
            $data = $request->all();
            $statusService = $enBanca->serviceActive($data['phone'], $data['document']);

            if ($statusService['status']) {
                return response()->json($statusService);
            }

            $res = $enBanca::validate($data);

            if (!$res['status']) {
                return response()->json([
                    'message'=> 'success',
                    'status' => true,
                    'data' => [
                        'validation' => false,
                        'message' => 'The service failed, the client is automatically rejected',
                        'phone' => $data['phone'],
                        'document' => $data['document']
                    ],
                    'code' => 200
                ]);
            }
            return response()->json($res);
        } catch (Exception $e) {
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
     * @param $limit
     * @return JsonResponse|BinaryFileResponse
     */
    public function  generateFile ($limit): JsonResponse|BinaryFileResponse
    {
        try {
            $enBanca = new EnBancaService();
            $res = $enBanca->generateFile($limit);
            if (!$res['status']) {
                return response()->json($res, $res['code']);
            }
//            return response()->json([
//                'code' => $res['code'],
//                'status' => $res['status'],
//                'message' => $res['message'],
//                'data' => Excel::download(new EnBancaExport($res['data']), 'enBancaMuestra.xlsx')
//            ]);
            return Excel::download(new EnBancaExport($res['data']), 'enBancaMuestra.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ], 500);
        } catch (GuzzleException $e) {
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
