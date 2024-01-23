<?php

namespace App\Http\Controllers;

use App\Models\CrCore\Ombu_cc_prestamos;
use App\Models\CrCore\Ombu_solicitudes;
use App\Models\CrCore\OmbuCcCuotas;
use App\Models\CrCore\OmbuPlans;
use App\Models\CrCore\Pagos;
use App\Models\CrCore\prod_products;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Validator;
use DateTime;
use DateInterval;
use App\Models\CrLog\RecalculateQuotasLogs;
use JetBrains\PhpStorm\ArrayShape;
use JWTAuth;
use App\Http\Integrations\RapiLoan\v1\Services\RapiLoanService;

class QuotasController extends Controller
{

    protected RapiLoanService $rapiLoanService;
    public function __construct(RapiLoanService $rapiLoanService)
    {
        $this->rapiLoanService = $rapiLoanService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getInfoQuotas(Request $request): JsonResponse
    {
        $date = new DateTime();
        $extraDays = 5;
        $date->add(new DateInterval("P{$extraDays}D"));
        try {
            $data = Validator::make($request->all(), [
                'document' => 'required'
            ]);

            if ($data->fails()) {
                return response()->json([
                    'message' => 'A document number is required to obtain the desired information.',
                    'error' => $data->errors()->getMessages(),
                    'status' => false,
                    'code' => 400
                ], 400);
            }

            $statusLoan = DB::connection('mysql')->table('config_parametros')
                ->where('grupo_id', '=', 24)
                ->where('nombre', '=', 'status_loan')
                ->where('estado', '=', 'HAB')
                ->first();

            if (isset($statusLoan) && $statusLoan->valor === 'active') {

                $credit = $this->rapiLoanService->getCashin($request->document, 'efecty');
                if ((int)$credit['codigo_respuesta'] === 0) {
                    $quotas = $credit['facturas'];
                    $totalQuotas = 0;
                    foreach ($quotas as $quota) {
                        $totalQuotas += (int)$quota['importe'];
                    }
                    $creditInfo[] = [
                        'total_debt' => $totalQuotas,
                        'quotas_debt' => count($quotas),
                        'credit_id' => $request->document
                    ];

                    $creditData = [
                        'person' => [
                            'id' => $request->document,
                            'document' => $request->document,
                            'phone' => 0,
                            'fullName' => $credit['nombre'] . ' ' . $credit['apellido']
                        ],
                        'quotas_info' => $creditInfo,
                    ];


                    if ($user = JWTAuth::parseToken()->authenticate()) {
                        DB::connection('logs')->table('efecty_log')->insert([
                            'user_id' => $user['id'],
                            'document' => $request->document,
                            'message' => 'search success',
                            'type' => 'info',
                            'type_service' => 'search',
                            'mount' => $creditInfo[0]['total_debt'],
                            'barra' => $quotas[0]['barra'],
                            'data' => json_encode($creditData, JSON_THROW_ON_ERROR)
                        ]);
                    }

                    return response()->json([
                        'data' => $creditData,
                        'message' => 'success',
                        'code' => 200,
                        'status' => true
                    ]);
                }
            }
            $credits = DB::connection('mysql')->table('ombu_cc_prestamos')
                ->join('ombu_personas', 'ombu_cc_prestamos.persona_id', '=', 'ombu_personas.id')
                ->where('ombu_cc_prestamos.estado', '!=', 'CANC')
                ->where('ombu_cc_prestamos.estado', '!=', 'ANUL')
                ->where('ombu_personas.nro_doc', '=', $request->document)
                ->select('ombu_cc_prestamos.id')
                ->get();

            $quotasCredit = [];
            foreach ($credits as $i => $credit) {
                $quotas = DB::connection('mysql')->table('ombu_cc_cuotas')
                    ->select(
                        'ombu_cc_cuotas.id',
                        'ombu_cc_cuotas.monto_cuota',
                        'ombu_cc_cuotas.monto_mora',
                        'ombu_cc_cuotas.total_pagado',
                        'ombu_cc_cuotas.prestamo_id'
                    )
                    ->where('ombu_cc_cuotas.estado', '!=', 'ANUL')
                    ->where('ombu_cc_cuotas.prestamo_id', '=', $credit->id)
                    ->where('ombu_cc_cuotas.fecha_venc', '<=', $date->format('Y-m-d'))
                    ->get();
                $quotasCredit[$i] = $quotas;
            }
            $payment = DB::connection('mysql')->table('pagos')->where('estado', '=', "Activo")->where('documento', '=', $request->document)->sum('monto');
            $payment = (int)$payment;
            $creditInfo = [];
            if ((count($credits) > 0) && count($quotasCredit[0]) > 0) {
                foreach ($quotasCredit as $iValue) {
                    $countQuotas = 0;
                    $totalQuotas = 0;
                    $credit_id = $iValue[0]->prestamo_id;
                    foreach ($iValue as $quota) {
                        $quota->monto_mora = $quota->monto_mora ?? 0;
                        $quota->total_pagado = $quota->total_pagado ?? 0;
                        $quota->monto_mora = (int)$quota->monto_mora;
                        $quota->monto_cuota = (int)$quota->monto_cuota;
                        $quota->total_pagado = (int)$quota->total_pagado;
                        $totalMount = $quota->monto_cuota + $quota->monto_mora;
                        if ($payment > 0) {
                            $payment -= $totalMount;
                            if ($payment < 0) {
                                $countQuotas++;
                                $debtPaid = abs($payment);
                                $totalQuotas += $debtPaid;
                                $credit_id = $quota->prestamo_id;
                            }
                        } else {
                            $countQuotas++;
                            $totalQuotas += $totalMount;
                            $credit_id = $quota->prestamo_id;
                        }
                    }
                    $creditInfo[] = [
                        'total_debt' => $totalQuotas,
                        'quotas_debt' => $countQuotas,
                        'credit_id' => $credit_id
                    ];
                }
                $people = DB::connection('mysql')->table('ombu_personas')
                    ->select('id', DB::connection('mysql')->raw('nro_doc as document'), DB::connection('mysql')->raw('tel_movil as phone'), DB::connection('mysql')->raw("CONCAT(nombre, ' ', apellido) as fullName"))
                    ->where('nro_doc', '=', $request->document)
                    ->get();

                $creditData = [
                    'person' => count($people) > 0 ? $people[0] : 'This person is not registered.',
                    'quotas_info' => $creditInfo,
                ];
                if ($user = JWTAuth::parseToken()->authenticate()) {
                    DB::connection('logs')->table('efecty_log')->insert([
                        'user_id' => $user['id'],
                        'document' => $request->document,
                        'message' => 'search success',
                        'type' => 'info',
                        'type_service' => 'search',
                        'mount' => $creditInfo[0]['total_debt'],
                        'data' => json_encode($creditData, JSON_THROW_ON_ERROR)
                    ]);
                }
                return response()->json([
                    'data' => $creditData,
                    'message' => 'success',
                    'code' => 200,
                    'status' => true
                ]);
            }

            return response()->json([
                'message' => 'This document number does not have any active credit, please try another.',
                'code' => 404,
                'status' => false
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'A system error has occurred, please try again later.',
                'errorFile' => $e->getFile(),
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'status' => false,
                'code' => 500
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function revalueQuotas(Request $request): JsonResponse
    {
        try {
            $totalPay = 0;
            $totalQuotas = 0;
            $result = [];
            //Obatian for all data the table Ombu_cc_prestamos
            $loan = new Ombu_cc_prestamos();
            $responseLoan = Ombu_cc_prestamos::find($request->id);
//            dd($responseLoan->id);
            //Condition check that the request id exists
            if ($responseLoan->id) {
                //obtain data the client
                $client = new Ombu_solicitudes();
                $responseCliente = $client->where('id', $responseLoan->id)->get()->first();
                $dataClient = json_decode($responseCliente['data'], false, 512, JSON_THROW_ON_ERROR);
                //Obtain actually date
                $date_actually = Carbon::now()->format('Y-m-d');
                //Search for all data the cuotas, with condition prestamo_id is equal with id the loan
                $quota = new OmbuCcCuotas();
                $responseQuotas = $quota->where('prestamo_id', $responseLoan->id)->where('fecha_venc', '<=', $date_actually)->get();
                foreach ($responseQuotas as $datQuotas) {
                    //Obtain value total the quotas
                    $datQuotas->monto_mora =  $datQuotas->monto_mora ?? 0;
                    $totalQuotas += $datQuotas->monto_cuota + $datQuotas->monto_mora;
                }
                //Obaitn all data the payment, until actually date
                $pay = new Pagos();
                $responsePay = $pay->where('documento', $dataClient->nro_doc)->where('fecha_hora_pago', '<=', $date_actually)->get();
                foreach ($responsePay as $dataPay) {
                    //obtain value total the pay
                    $totalPay += $dataPay->monto;
                }
                //Obatin percentaje total the totalQuotas
                $percent = round(($totalPay * 100) / $totalQuotas);
                //Calculate days in mora
                $responseDuesW = $quota->where('prestamo_id', $responseLoan->id)->where('fecha_venc', '<=', $date_actually)->orderBy('fecha_venc', 'DESC')->get()->first();
                $date_defeated = Carbon::parse($responseDuesW->fecha_venc);
                //Compare date actually and date the dues
                $total_date = (int)(strtotime(Carbon::now()->format('Y-m-d')) - strtotime($date_defeated->format('Y-m-d'))) / 86400;
                if ($percent >= 90 && $total_date <= 30) {
                    //Obatian new capital
                    if ($percent < 100) {
                        $new_capital = $request->newValue;
                    } else {
                        $new_capital = $responseDuesW->capital_restante;
                    }
                    $result = $this->recalculateValue($new_capital, $request);
                } else {
                    $result = [
                        'message' => 'Due to internal policy, the request cannot be sent. ',
                        'percent' => $percent,
                        'total_date' => $total_date
                    ];
                }
            }
//            foreach ($responseLoan as $dataLoan) {
//            }
            //$this->recalulateQuotasSave('succes',json_encode($result),200);
            return response()->json([
                'data' => $result,
                'message' => 'refinance success',
                'code' => 200,
                'status' => true
            ], 200);
        } catch (Exception $e) {
            $this->recalulateQuotasSave('error', $e->getMessage(), $e->getCode());
            return response()->json([
                'message' => 'A system error has occurred, please try again later.',
                'errorFile' => $e->getFile(),
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'status' => false
            ], 500);
        }
    }

    /**
     * @param $new_capital
     * @param $request
     * @return array
     */
    private function recalculateValue($new_capital, $request): array
    {
        try {
            $request->month *= 2;
            $plans = new OmbuPlans();
            $showNew = [];
            $responsePlan = $plans->where('id', 25)->get()->first();
            //interest_rate the bussines
            $interest_rate = (1 + $responsePlan->tasa_nominal_anual / 100) ** (1 / 24) - 1;
            $interest_rate = number_format($interest_rate, 4);
            //generate new quotas months
            $bail = 25000;
            //calculate iva * bail
            $quotas_month = $new_capital * ($interest_rate * ((1 + $interest_rate) ** $request->month) / (((1 + $interest_rate) ** $request->month) - 1));
            $ivaBail = $bail * 0.19;
            $interest = 0;
            //save information in the table ombu_cc_cuotas
            for ($i = 1; $i <= $request->month; $i++) {
                $interest = $new_capital * $interest_rate;
//                $interest = $interest;
                //Obtain value all the capital
                $capital = $quotas_month - $interest;
                //Obatain value cost total for te client
                $totalClient = $quotas_month + $bail + $ivaBail;
                $new_capital -= $capital;
                if ($new_capital <= 10) {
                    $new_capital = 0;
                }
                $showNew[] = [
                    'cuota_nro' => $i,
                    'monto_total' => round($totalClient, 0),
                    'interest' => $interest,
                    'amortizacion' => round($capital, 2),
                    'fianza' => $bail,
                    'iva_fianza_porcentaje' => 19,
                    'iva_fianza' => $ivaBail,
                    'capital_restante' => $new_capital,
                    'estado' => 'PEND',
                    'tipo_moneda' => 'COP',
                    'fecha_venc' => Carbon::parse($request->fecha_v)->addDays(15 * $i)->format('Y-m-d'),
                    'prestamo_id' => $request->id,
                ];
            }
            return $showNew;
        } catch (Exception $e) {
            return [
                'message' => 'A system error has occurred, please try again later.',
                'code' => $e->getCode(),
                'exceptionMessage' => $e->getMessage(),
                'status' => false
            ];
        }
    }

    /**
     * @param $type
     * @param $message
     * @param $code
     * @return void
     */
    #[ArrayShape(['message' => "string", 'errorFile' => "string", 'errorMessage' => "string", 'errorLine' => "int", 'status' => "false"])] private function recalulateQuotasSave($type, $message, $code): void
    {
        try {
            $r_quota = new RecalculateQuotasLogs();
            $r_quota->type = $type;
            $r_quota->message = $message;
            $r_quota->code = $code;
            $r_quota->created_at = date('Y-m-d H:i:s');
            $r_quota->updated_at = date('Y-m-d H:i:s');
            $r_quota->save();
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * @param Request $response
     * @return JsonResponse
     */
    public function updateQuota(Request $response): JsonResponse
    {
        try {
            //Obatian for all data the table Ombu_cc_prestamos
            $loan = new Ombu_cc_prestamos();
            $responseProduct = prod_products::where("id", "=", $response->id_product)->first();
            $responseLoan = $loan->where('estado', '!=', 'CANC')->where('id', "=", $response->id_loan)->get();
            foreach ($responseLoan as $dataLoan) {
                //Condition check that the request id exists
                if ($dataLoan->solicitud_id) {
                    //Search for all data the cuotas, with condition prestamo_id is equal with id the loan
                    $quota = new OmbuCcCuotas();
                    //obtain data the loan
                    $solicitude = Ombu_solicitudes::where("id", "=", $dataLoan->solicitud_id)->first();
                    $data = json_decode($solicitude->data, false, 512, JSON_THROW_ON_ERROR);
                    $data->producto_id = (string)$responseProduct->id;
                    $data->int_monto_financiar = (string)$response->newValue;
                    $solicitude->data = json_encode($data, JSON_THROW_ON_ERROR);
                    $solicitude->update();
                    $responseQuotas = $quota->where('prestamo_id', $dataLoan->id)->get();
                    foreach ($responseQuotas as $updateQuota) {
                        $updateQuota->estado = 'REFIN';
                        $updateQuota->update();
                    }
                    foreach ($response->data as $addRefinance) {
                        $quotaSave = new OmbuCcCuotas();
                        $quotaSave->monto_cuota = $addRefinance['monto_total'];
                        $quotaSave->interes_cuota = $addRefinance['interest'];
                        $quotaSave->amortizacion = $addRefinance['amortizacion'];
                        $quotaSave->fianza = $addRefinance['fianza'];
                        $quotaSave->iva_fianza_porcentaje = $addRefinance['iva_fianza_porcentaje'];
                        $quotaSave->iva_fianza = $addRefinance['iva_fianza'];
                        $quotaSave->capital_restante = $addRefinance['capital_restante'];
                        $quotaSave->estado = $addRefinance['estado'];
                        $quotaSave->tipo_moneda = $addRefinance['tipo_moneda'];
                        $quotaSave->cuota_nro = $addRefinance['cuota_nro'];
                        $quotaSave->fecha_venc = date($addRefinance['fecha_venc']);
                        $quotaSave->prestamo_id = $addRefinance['prestamo_id'];
                        $quotaSave->save();
                    }
                }

            }
            return response()->json([
                'message' => 'refinance success',
                'code' => 200,
                'status' => true
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'A system error has occurred, please try again later.',
                'errorFile' => $e->getFile(),
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'status' => false
            ], 500);
        }
    }
}
