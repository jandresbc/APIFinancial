<?php

namespace App\Http\Controllers;

use App\Http\Integrations\Nuovo;
use App\Http\Services\TrustonicService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\CrCore\OmbuCobros;
use App\Models\CrCore\OmbuCcCuotas;
use App\Models\CrCore\CuotasHist;
use App\Http\Integrations\Payment;
use DateTime;
use App\Http\Integrations\RapiLoan\v1\Services\RapiLoanService;


class PaymentsController extends Controller
{

    protected RapiLoanService $rapiLoanService;
    public function __construct(RapiLoanService $rapiLoanService)
    {
        $this->rapiLoanService = $rapiLoanService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception|GuzzleException
     */
    public function paymentConfirmation(Request $request): JsonResponse{
        $newDate = new DateTime();
        $data = Validator::make($request->all(), [
            'amount' => 'required',
            'document' => 'required',
            'datetime_payment' => 'required',
            'credit_id' => 'required',
            'voucher_number' => 'required',
            'user_id' => 'required'
        ]);
        $resultCharge = DB::connection('mysql')->table('ombu_cobros')
            ->where('nro_comprobante', '=', $request->voucher_number)
            ->exists();
        if ($resultCharge) {
            return response()->json([
                'message' => 'The voucher number already exists in our system.',
                'status' => false,
                'code' => 400
            ], 400);
        }
        $datePayment = new DateTime($request->datetime_payment);
        // if ($datePayment->format('Y-m-d H:i') < $newDate->format('Y-m-d H:i')) {
        //     return response()->json([
        //         'message' => 'The date is less than today.',
        //         'status' => false,
        //         'code' => 400
        //     ], 400);
        // }
        if ($data->fails()) {
            return response()->json([
                'message' => 'Missing data please rectify the information sent.',
                'error' => $data->errors()->getMessages(),
                'status' => false,
                'code' => 400
            ], 400);
        }
        DB::connection('mysql')->beginTransaction();
        try {
//            $quotaCredit = DB::connection('mysql')->table('ombu_cc_cuotas')
//                ->join('ombu_cc_prestamos', 'ombu_cc_prestamos.id', '=', 'ombu_cc_cuotas.prestamo_id')
//                ->where('ombu_cc_cuotas.prestamo_id', '=', $request->credit_id)
//                ->where('ombu_cc_cuotas.fecha_venc', '<=', date("Y-m-d",time()))
//                ->select('ombu_cc_cuotas.*')
//                ->get();
//            $totalQuota = DB::connection('mysql')->table('ombu_cc_cuotas as oc')
//                ->join('ombu_cc_prestamos as ocp', 'ocp.id', '=', 'oc.prestamo_id')
//                ->where('oc.prestamo_id', '=', $request->credit_id)
//                ->where('oc.fecha_venc', '<=', date("Y-m-d",time()))
//                ->select('oc.*')
//                ->sum(DB::raw('oc.monto_cuota + IFNULL(oc.monto_mora,0)'));
//            $payment = DB::connection('mysql')->table('pagos')->where('documento', '=', $request->document)->sum('monto');

//            $totalQuota = (float)number_format($totalQuota, 2, ',', '');
//            $creditInfo = [];
//            foreach ($quotaCredit as $quota) {
//                $quota->monto_mora = $quota->monto_mora ?? 0;
//                $quota->total_pagado = $quota->total_pagado ?? 0;
//                $quota->monto_mora = (int)number_format($quota->monto_mora, 0, ',', '');
//                $quota->monto_cuota = (int)number_format($quota->monto_cuota, 0, ',', '');
//                $quota->total_monto =  $quota->monto_cuota + $quota->monto_mora;
//                if ($payment > 0) {
//                    $payment -= $quota->total_monto;
//                    if ($payment < 0) {
//                        $quota->total_monto = abs($payment);
//                        $creditInfo[] = $quota;
//                    }
//                } else {
//                    $creditInfo[] = $quota;
//                }
//            }
            $request->amount = (int)$request->amount;
            $hash = Hash::make(json_encode([
                'prestamo_id' => $request->credit_id,
                'monto' => $request->amount,
                'documento' => $request->document,
                'fecha_hora_pago' => $request->datetime_payment,
                'plataforma' => 'efecty',
                'pagador' => 'titular',
                'created_at' => $newDate->format('Y-m-d H:i:s')
            ], JSON_THROW_ON_ERROR));
//                $date_payment = new DateTime($request->datetime_payment);
            if ($request->amount > 0) {
//                    $capitalMoney = $request->amount;
                /*if ($request->amount <= $totalQuota) {
                    foreach ($creditInfo as $quota) {
                        $hash = Hash::make(json_encode([
                            'prestamo_id' => $request->credit_id,
                            'cuota_id' => $quota->id,
                            'monto' => $capitalMoney,
                            'data' => json_encode(["fecha_hora" => $request->datetime_payment], JSON_THROW_ON_ERROR),
                            'tipo_moneda' => 'COP',
                            'fecha_de_cobro' => $date_payment->format('Y-m-d'),
                            'estado' => 'ACRE',
                            'sys_fecha_alta' => $newDate->format('Y-m-d H:i:s'),
                            'sys_fecha_modif' => $newDate->format('Y-m-d H:i:s'),
                            'sys_usuario_id' => $request->user_id,
                            'nro_comprobante' => $request->voucher_number,
                            'broker_id' => 4
                        ], JSON_THROW_ON_ERROR));
                        if ($capitalMoney >= $quota->total_monto) {
                            //$this->insertPayment($quota, $request, $hash);
                            $capitalMoney -= $quota->total_monto;
                        } else if($capitalMoney > 0) {
                            /*$newOmbuCobros = OmbuCobros::create([
                                'prestamo_id' => $request->credit_id,
                                'cuota_id' => $quota->id,
                                'monto' => $capitalMoney,
                                'data' => json_encode(["fecha_hora" => $request->datetime_payment], JSON_THROW_ON_ERROR),
                                'data_id' => $hash,
                                'tipo_moneda' => 'COP',
                                'fecha_de_cobro' => $date_payment->format('Y-m-d'),
                                'estado' => 'ACRE',
                                'sys_fecha_alta' => $newDate->format('Y-m-d H:i:s'),
                                'sys_fecha_modif' => $newDate->format('Y-m-d H:i:s'),
                                'sys_usuario_id' => $request->user_id,
                                'nro_comprobante' => $request->voucher_number,
                                'broker_id' => 4
                            ]);*/
                /*$quotaT = OmbuCcCuotas::find($quota->id);
                $quotaT->cobro_id = $newOmbuCobros->id;
                $quotaT->estado = 'PEND';
                $quotaT->total_pagado = $capitalMoney;
                //$quotaT->save();

                /*CuotasHist::create([
                    'cuota_id' => $quota->id,
                    'prestamo_id' => $request->credit_id,
                    'estado' => 'PAGP',
                    'estado_anterior' => 'PEND',
                    'monto' => $capitalMoney,
                    'operacion' => 'Pago',
                    'monto_pago' => $capitalMoney,
                    'cobro_id' => $newOmbuCobros->id,
                    'resto' => $quota->total_monto - $capitalMoney,
                    'sys_fecha_alta' => $newDate->format('Y-m-d H:i:s'),
                    'sys_fecha_modif' => $newDate->format('Y-m-d H:i:s'),
                ]);

                $capitalMoney -= $capitalMoney;
            }
        }
    } else if ($request->amount > $totalQuota) {
        foreach ($creditInfo as $quota){
            $hash = Hash::make(json_encode([
                'prestamo_id' => $request->credit_id,
                'cuota_id' => $quota->id,
                'monto' => $capitalMoney,
                'data' => json_encode(["fecha_hora" => $request->datetime_payment], JSON_THROW_ON_ERROR),
                'tipo_moneda' => 'COP',
                'fecha_de_cobro' => $date_payment->format('Y-m-d'),
                'estado' => 'ACRE',
                'sys_fecha_alta' => $newDate->format('Y-m-d H:i:s'),
                'sys_fecha_modif' => $newDate->format('Y-m-d H:i:s'),
                'sys_usuario_id' => $request->user_id,
                'nro_comprobante' => $request->voucher_number,
                'broker_id' => 4
            ], JSON_THROW_ON_ERROR));
            if ($capitalMoney >= $quota->total_monto) {
                //$this->insertPayment($quota, $request, $hash);
                $capitalMoney -= $quota->total_monto;
            }
        }
        if ($capitalMoney > 0) {
            $quotasPayment = array_map(static function ($item) {
                return $item->cuota_nro;
            }, $creditInfo);
            $quotasNew = DB::connection('mysql')->table('ombu_cc_cuotas')
                ->where('ombu_cc_cuotas.prestamo_id', '=', $request->credit_id)
                ->whereNotIn('cuota_nro', $quotasPayment)
                ->get();
            if (isset($quotasNew[0]->capital_restante)) {
                $tasa_interes = 0.0165;
                $newCapital_restante = $quotasNew[0]->capital_restante - $capitalMoney;
                $cuotaMensual = $newCapital_restante * (($tasa_interes * ((1 + $tasa_interes) ** count($quotasNew)))/(((1+$tasa_interes) ** count($quotasNew)) - 1));
                $fianza = 25000;
                $ivaFianza = $fianza * 0.19;
                $cuotaMensual = (int)$cuotaMensual;
                $saldoCapital = (int)$newCapital_restante;
                $capital = 0;
                foreach ($quotasNew as $i => $item){
                    if ($i === 0) {
                        $interest = $saldoCapital * $tasa_interes;
                        $interest = (int)$interest;
                        $capital = $cuotaMensual - $interest;
                        $totalClient = $capital + $interest + $fianza + $ivaFianza;
//                                        echo 'saldo capital: ' . $newCapital_restante . ' capital: ' . $capital . ' interes: ' . $interes . ' fianza: ' . $fianza . ' iva fianza: ' . $ivaFianza . ' total cobro cliente: ' . $totalClient . '</br>';
                        $quotaT = OmbuCcCuotas::find($item->id);
                        $quotaT->monto_cuota = $totalClient;
                        $quotaT->interes_cuota = $interest;
                        $quotaT->amortizacion = $capital;
                        $quotaT->capital_restante = $newCapital_restante;
                        //$quotaT->save();
                    } else if ($i > 0) {
                        $saldoCapital -= $capital;
                        $interest = $saldoCapital * $tasa_interes;
                        $interest = (int)$interest;
                        $capital = $cuotaMensual - $interest;
                        $totalClient = $capital + $interest + $fianza + $ivaFianza;
//                                        echo 'saldo capital: ' . $saldoCapital . ' capital: ' . $capital . ' interes: ' . $interes . ' fianza: ' . $fianza . ' iva fianza: ' . $ivaFianza . ' total cobro cliente: ' . $totalClient . '</br>';
                        $quotaT = OmbuCcCuotas::find($item->id);
                        $quotaT->monto_cuota = $totalClient;
                        $quotaT->interes_cuota = $interest;
                        $quotaT->amortizacion = $capital;
                        $quotaT->capital_restante = $saldoCapital;
                        //$quotaT->save();
                    }
                }
            }
        }
    }*/
                $dataCr = DB::connection('mysql')->table('ombu_cc_prestamos')
                    ->join('ombu_personas', 'ombu_cc_prestamos.persona_id', '=', 'ombu_personas.id')
                    ->join('ombu_solicitudes', 'ombu_solicitudes.id', '=', 'ombu_cc_prestamos.solicitud_id')
                    ->where('ombu_cc_prestamos.estado', '!=', 'CANC')
                    ->where('ombu_cc_prestamos.estado', '!=', 'ANUL')
                    ->where('ombu_personas.nro_doc', '=', $request->document)
                    ->select(
                        'ombu_cc_prestamos.id',
                        'ombu_cc_prestamos.nuovo_id',
                        'ombu_cc_prestamos.type_enrollment_id',
                        'ombu_solicitudes.data'
                    )
                    ->first();

                $paymentClient = DB::connection('mysql')->table('pagos')->insert([
                    'prestamo_id' => $dataCr->id ?? $request->document,
                    'monto' => $request->amount,
                    'documento' => $request->document,
                    'fecha_hora_pago' => $request->datetime_payment,
                    'plataforma' => 'efecty',
                    'pagador' => 'titular',
                    'created_at' => $newDate->format('Y-m-d H:i:s')
                ]);

                DB::connection('logs')->table('efecty_log')->insert([
                    'user_id' => $request->user_id,
                    'datePayment' => $request->datetime_payment,
                    'document' => $request->document,
                    'message' => 'payment confirmation',
                    'type' => 'info',
                    'type_service' => 'payment',
                    'mount' => $request->amount,
                    'data' => json_encode([
                        $request->all(),
                        'hash' => $hash,
                        'payment_status' => $paymentClient
                    ], JSON_THROW_ON_ERROR)
                ]);

                DB::connection('mysql')->commit();

                $statusLoan = DB::connection('mysql')->table('config_parametros')
                    ->where('grupo_id', '=', 24)
                    ->where('nombre', '=', 'status_loan')
                    ->where('estado', '=', 'HAB')
                    ->first();
                if (isset($statusLoan) && $statusLoan->valor === 'active') {
                    $pay = $this->rapiLoanService->uploadPayment($request->all(), 'efecty');
                }
                $credit = DB::connection('mysql')->table('ombu_cc_prestamos')
                    ->join('ombu_solicitudes', 'ombu_solicitudes.id', '=', 'ombu_cc_prestamos.solicitud_id')
                    ->where('ombu_cc_prestamos.id', '=', $request->credit_id)
                    ->select(
                        'ombu_cc_prestamos.id',
                        'ombu_cc_prestamos.nuovo_id',
                        'ombu_cc_prestamos.type_enrollment_id',
                        'ombu_solicitudes.data'
                    )
                    ->first();
                if (isset($credit)) {
                    $totalQuota = DB::connection('mysql')->table('ombu_cc_cuotas as oc')
                        ->join('ombu_cc_prestamos as ocp', 'ocp.id', '=', 'oc.prestamo_id')
                        ->where('oc.prestamo_id', '=', $credit->id)
                        ->where('oc.fecha_venc', '<=', date("Y-m-d",time()))
                        ->select('oc.*')
                        ->sum(DB::raw('oc.monto_cuota + IFNULL(oc.monto_mora,0)'));
                    $payments = DB::connection('mysql')->table('pagos')
                        ->where('documento', '=', $request->document)
                        ->where('estado', '=', 'Activo')
                        ->where('prestamo_id', '=', $credit->id)
                        ->sum('monto');

                    $totalMount = (int)$totalQuota * 0.8;

                    if ((int)$payments >= (int)$totalMount) {
                        switch ((string)$credit->type_enrollment_id) {
                            case '1':
                                $nuovo = new Nuovo();
                                $nuovo->unlockPhone($credit->nuovo_id);
                                break;
                            case '2':
                                $dataCredit = json_decode($credit->data, false, 512, JSON_THROW_ON_ERROR);
                                $trustonic = new TrustonicService();
                                if (isset($dataCredit->imei)) {
                                    $result = $trustonic->update([
                                        'imei' => $dataCredit->imei,
                                        'polity' => 'A11Financed'
                                    ]);

                                    if (!$result['status']) {
                                        DB::connection('logs')->table('efecty_log')->insert([
                                            'user_id' => $request->user_id,
                                            'datePayment' => $request->datetime_payment,
                                            'document' => $request->document,
                                            'message' => '',
                                            'type' => 'error',
                                            'type_service' => 'payment',
                                            'mount' => $request->amount,
                                            'data' => json_encode([
                                                'errorMessage' => $result['errorMessage'],
                                                'errorFile' => $result['errorFile'],
                                                'errorLine' => $result['errorLine'],
                                            ], JSON_THROW_ON_ERROR)
                                        ]);
                                        return response()->json($result, $result['code']);
                                    }
                                }
                                break;
                        }
                    }
                }
                return response()->json([
                    'message' => 'success',
                    'data' => [
                        'payment_data' => [
                            'payment_status' => $paymentClient,
                            'value_payment' => $request->amount,
                            'document' => $request->document,
                            'voucher_number' => $request->voucher_number,
                            'date_payment' => $newDate->format('Y-m-d H:i:s'),
                            'hash' => $hash
                        ]
                    ],
                    'code' => 200,
                    'status' => true
                ]);
            }
            if ($request->amount === 0) {
                return response()->json([
                    'message' => 'The amount cannot be 0 please check and try again.',
                    'code' => 400,
                    'status' => false
                ], 400);
            }
            return response()->json([
                'message' => 'We cannot identify a credit with this ID, please validate your information.',
                'code' => 400,
                'status' => false
            ], 400);
        } catch (Exception $e) {
            DB::connection('mysql')->rollBack();
            DB::connection('logs')->table('efecty_log')->insert([
                'user_id' => $request->user_id,
                'datePayment' => $request->datetime_payment,
                'document' => $request->document,
                'message' => $e->getMessage(),
                'type' => 'error',
                'type_service' => 'payment',
                'mount' => $request->amount,
                'data' => json_encode([
                    'errorLine' => $e->getLine(),
                    'errorFile' => $e->getFile(),
                ], JSON_THROW_ON_ERROR)
            ]);
            return response()->json([
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }

    /**
     * @throws \JsonException
     * @throws Exception
     */
    protected function insertPayment($quota, $request, $hash): void
    {
        $date_payment = new DateTime($request->datetime_payment);
        $newOmbuCobros = OmbuCobros::create([
            'prestamo_id' => $request->credit_id,
            'cuota_id' => $quota->id,
            'monto' => $quota->monto_cuota + $quota->monto_mora,
            'data' => json_encode(["fecha_hora" => $request->datetime_payment], JSON_THROW_ON_ERROR),
            'data_id' => $hash,
            'tipo_moneda' => 'COP',
            'fecha_de_cobro' => $date_payment->format('Y-m-d'),
            'estado' => 'PEND',
            'sys_fecha_alta' => date('Y-m-d H:i:s'),
            'sys_usuario_id' => $request->user_id,
            'nro_comprobante' => $request->voucher_number,
            'broker_id' => 4
        ]);

        $quotaT = OmbuCcCuotas::find($quota->id);
        $quotaT->cobro_id = $newOmbuCobros->id;
        $quotaT->estado = 'HOLD';
        $quotaT->total_pagado = $quota->monto_cuota + $quota->monto_mora;
        $quotaT->save();

        OmbuCobros::where('id', $newOmbuCobros->id)
            ->update([
                'estado' => 'ACRE',
                'sys_fecha_modif' => date('Y-m-d H:i:s')
            ]);

        $quotaT = OmbuCcCuotas::find($quota->id);
        $quotaT->estado = 'CANC';
        $quotaT->save();

        CuotasHist::create([
            'cuota_id' => $quota->id,
            'prestamo_id' => $request->credit_id,
            'estado' => 'PAGP',
            'estado_anterior' => 'PEND',
            'monto' => $quota->monto_cuota + $quota->monto_mora,
            'operacion' => 'Pago',
            'monto_pago' => $quota->monto_cuota + $quota->monto_mora,
            'cobro_id' => $newOmbuCobros->id,
            'resto' => $quota->total_monto - ($quota->monto_cuota + $quota->monto_mora),
            'sys_fecha_alta' => date('Y-m-d H:i:s'),
            'sys_fecha_modif' => date('Y-m-d H:i:s'),
        ]);
    }

    public function searchPayment(Request $request): JsonResponse
    {
        $document = trim($request->get('documentClient'));
        try {
            $payments = DB::table('pagos')
                ->where('documento', '=', $document)
                ->get();

            if (count($payments) > 0) {
                return response()->json([
                    'status' => true,
                    'code' => 200,
                    'message' => 'Payments by client with number document: ' . $document . '.',
                    'data' => [
                        'result' => $payments,
                        'document' => $document
                    ]
                ], 200);
            }

            return response()->json([
                'message' => 'This document number does not have payments in the system and does not have any credit.',
                'status' => false,
                'code' => 404
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorDescription' => $e->getMessage(),
                'errorData' => [
                    'error' => $e
                ]
            ], 500);
        }
    }

    /**
     * @throws Exception
     */
    public function createPayment (Request $request): JsonResponse
    {
        $document = trim($request->get('document'));
        $dateFormat = new \DateTime(trim($request->get('date_payment')));

        $credit = DB::table('ombu_cc_prestamos')
            ->join('ombu_personas', 'ombu_cc_prestamos.persona_id', '=', 'ombu_personas.id')
            ->where('ombu_cc_prestamos.estado', '!=', 'CANC')
            ->where('ombu_cc_prestamos.estado', '!=', 'ANUL')
            ->where('ombu_personas.nro_doc', '=', $document)
            ->select('ombu_cc_prestamos.id')
            ->first();

        $paymentValidate = DB::table('pagos')
            ->where('monto', '=', trim($request->get('mount')))
            ->where('fecha_hora_pago', '=', $dateFormat->format('Y-m-d H:i:s'))
            ->where('documento', '=', $document)
            ->exists();
        if ($paymentValidate) {
            return response()->json([
                'message' => 'This payment already exist in the system.',
                'status' => false,
                'code' => 400
            ], 400);
        }
        try {
            if (isset($credit)) {
                DB::table('pagos')
                    ->insert([
                        'documento' => $document,
                        'monto' => trim($request->get('mount')),
                        'fecha_hora_pago' => trim($request->get('date_payment')),
                        'plataforma' => trim($request->get('platform')),
                        'prestamo_id' => $credit->id,
                        'pagador' => 'titular',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                return response()->json([
                    'status' => true,
                    'code' => 200,
                    'message' => 'Payment register is successful.'
                ], 200);
            }
            return response()->json([
                'status' => true,
                'code' => 400,
                'message' => 'Payment is not register in the system, please try again later.'
            ], 400);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorDescription' => $e->getMessage(),
                'errorData' => [
                    'error' => $e
                ]
            ], 500);
        }
    }

    /**
     * @throws Exception
     */
    public function createNewPayment (Request $request): JsonResponse
    {
        $document = trim($request->get('new-document'));
        $dateFormat = new \DateTime(trim($request->get('date_payment')));

        $credit = DB::table('ombu_cc_prestamos')
            ->join('ombu_personas', 'ombu_cc_prestamos.persona_id', '=', 'ombu_personas.id')
            ->where('ombu_cc_prestamos.estado', '!=', 'CANC')
            ->where('ombu_cc_prestamos.estado', '!=', 'ANUL')
            ->where('ombu_personas.nro_doc', '=', $document)
            ->select('ombu_cc_prestamos.id')
            ->first();

        $paymentValidate = DB::table('pagos')
            ->where('monto', '=', trim($request->get('mount')))
            ->where('fecha_hora_pago', '=', $dateFormat->format('Y-m-d H:i:s'))
            ->where('documento', '=', $document)
            ->exists();
        if ($paymentValidate) {
            return response()->json([
                'message' => 'This payment already exist in the system.',
                'status' => false,
                'code' => 400
            ], 400);
        }
        try {
            DB::table('pagos')
                ->insert([
                    'documento' => $document,
                    'monto' => trim($request->get('new-mount')),
                    'fecha_hora_pago' => trim($request->get('new-date_payment')),
                    'plataforma' => trim($request->get('new-platform')),
                    'prestamo_id' => $credit->id ?? null,
                    'pagador' => isset($credit) ? 'titular' : 'tercero',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Payment register is successful.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorDescription' => $e->getMessage(),
                'errorData' => [
                    'error' => $e
                ]
            ], 500);
        }
    }

    public function updatePayment (Request $request): JsonResponse
    {
        $document = trim($request->get('update-document'));
        $id = trim($request->get('update-id'));

        $credit = DB::table('ombu_cc_prestamos')
            ->join('ombu_personas', 'ombu_cc_prestamos.persona_id', '=', 'ombu_personas.id')
            ->where('ombu_cc_prestamos.estado', '!=', 'CANC')
            ->where('ombu_cc_prestamos.estado', '!=', 'ANUL')
            ->where('ombu_personas.nro_doc', '=', $document)
            ->select('ombu_cc_prestamos.id')
            ->first();
        try {
            DB::table('pagos')
                ->where('id', '=' , $id)
                ->update([
                    'documento' => $document,
                    'monto' => trim($request->get('update-mount')),
                    'fecha_hora_pago' => trim($request->get('update-date-payment')),
                    'plataforma' => trim($request->get('update-platform')),
                    'prestamo_id' => $credit->id ?? null,
                    'pagador' => isset($credit) ? 'titular' : 'tercero',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Payment updated is successful.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorDescription' => $e->getMessage(),
                'errorData' => [
                    'error' => $e
                ]
            ], 500);
        }
    }
    public function PaymentMethod(Request $request): JsonResponse
    {
        try{
            $payment = new Payment();
            $response = $payment->conectionPaymantez($request);
            return response()->json([$response]);
        }catch(Exception $e){
            return  response()->json([
                'message' => 'Ha ocurrido un error en el sistema al generar el link, por favor intente más tarde',
                'errorMessage' => $e->getMessage(),
                'errorCode' => $e->getCode(),
                'code' => 500,
                'status' => false
            ],500);
        }
    }
}
