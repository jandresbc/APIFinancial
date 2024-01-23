<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Services\MotorRiesgoService;

class MotorRiesgoController extends Controller
{
    //Evalua el riesgo de un cliente
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws GuzzleException
     * @throws \JsonException}
     */
    public function getRisk(Request $request): JsonResponse
    {
        $risk = new MotorRiesgoService();

        $res = $risk->createRiskDecision($request->all());

        if (!$res['status']) {
            return response()->json($res, $res['code']);
        }
        return response()->json($res);
    }

    //Valida el stado de la evaluación de riesgo de un cliente
    public function getStatus(Request $request)
    {
        $motorServices = new MotorRiesgoService();
        return $motorServices->getRisksDecision($request);
    }
}
