<?php

namespace App\Http\Controllers\RapiLoan\v1;

use App\Http\Controllers\Controller;
use App\Http\Integrations\RapiLoan\v1\Resources\CashinGetResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Integrations\RapiLoan\v1\Resources\CashinPayResource;
use App\Http\Integrations\RapiLoan\v1\Services\RapiLoanService;
use Illuminate\Http\Request;

class RapiLoanController extends Controller
{
    protected $rapiLoanService;

    public function __construct(RapiLoanService $rapiLoanService)
    {
        $this->rapiLoanService = $rapiLoanService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return CashinGetResource
     */
    public function getCashin(Request $request): CashinGetResource | JsonResponse
    {
        try {
            $data = Validator::make($request->all(), ['document' => 'required']);
            if ($data->fails()) {
                return response()->json([
                    'message' => 'El número de cedula es requerido.',
                    'error' => $data->errors()->getMessages(),
                    'status' => false,
                    'code' => 400
                ], 400);
            }

            return new CashinGetResource($this->rapiLoanService->getCashin($request->document, $request->channel));
        } catch (\Throwable $th) {
            die($th);
        }
    }

    public function getCashinPay(Request $request)
    {
        try {
            $data = Validator::make($request->all(), ['document' => 'required']);
            if ($data->fails()) {
                return response()->json([
                    'message' => 'El documento es requerido.',
                    'error' => $data->errors()->getMessages(),
                    'status' => false,
                    'code' => 400
                ], 400);
            }

            $data = Validator::make($request->all(), ['id_number' => 'required']);
            if ($data->fails()) {
                return response()->json([
                    'message' => 'El id numérico es requerido.',
                    'error' => $data->errors()->getMessages(),
                    'status' => false,
                    'code' => 400
                ], 400);
            }

            $data = Validator::make($request->all(), ['bar_code' => 'required']);
            if ($data->fails()) {
                return response()->json([
                    'message' => 'El código de barra es obligatorio.',
                    'error' => $data->errors()->getMessages(),
                    'status' => false,
                    'code' => 400
                ], 400);
            }

            $data = Validator::make($request->all(), ['amount' => 'required']);
            if ($data->fails()) {
                return response()->json([
                    'message' => 'El monto a cobrar es requerido.',
                    'error' => $data->errors()->getMessages(),
                    'status' => false,
                    'code' => 400
                ], 400);
            }

            $data = Validator::make($request->all(), ['date' => 'required']);
            if ($data->fails()) {
                return response()->json([
                    'message' => 'La fecha de cobro es requerida.',
                    'error' => $data->errors()->getMessages(),
                    'status' => false,
                    'code' => 400
                ], 400);
            }

            return new CashinPayResource($this->rapiLoanService->getCashinPay($request->id_number, $request->document, $request->bar_code, $request->amount, $request->date, $request->channel));
        } catch (\Throwable $th) {
            die($th);
        }
    }
}
