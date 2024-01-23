<?php

namespace App\Http\Controllers;

use App\Http\Integrations\RapiLoan\v1\Services\RapiLoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Exception;

class OmbuCcPrestamosController extends Controller
{

    protected RapiLoanService $rapiLoanService;
    public function __construct(RapiLoanService $rapiLoanService)
    {
        $this->rapiLoanService = $rapiLoanService;
    }
    /**
     * Update the specified user.
     *
     * @param string $nro_doc
     * @return JsonResponse
     */
    public function checkCreditUser (string $nro_doc): JsonResponse {
        try {
            $statusLoan = DB::connection('mysql')->table('config_parametros')
                ->where('grupo_id', '=', 24)
                ->where('nombre', '=', 'status_loan')
                ->where('estado', '=', 'HAB')
                ->first();

            if (isset($statusLoan) && $statusLoan->valor === 'active') {

                $credit = $this->rapiLoanService->getCashin($nro_doc, 'efecty');
                if ((int)$credit['codigo_respuesta'] === 0) {
                    $quotas = $credit['facturas'];
                    $totalQuotas = 0;
                    foreach ($quotas as $quota) {
                        $totalQuotas += (int)$quota['importe'];
                    }

                    return response()->json([
                        'data' => [
                            'credit_user_info' => [
                                'tel_movil' => '',
                                'nombreCompleto' => $credit['nombre'] . ' ' . $credit['apellido'],
                                'email' => ''
                            ],
                            'balance' => max($totalQuotas, 0)
                        ],
                        'message' => 'success',
                        'code' => 200,
                        'status' => true
                    ]);
                }
            }

            $result = DB::connection('mysql')->table('ombu_cc_prestamos')
                ->join('ombu_personas', 'ombu_cc_prestamos.persona_id', '=', 'ombu_personas.id')
                ->select('ombu_cc_prestamos.id as prestamo_id', 'ombu_personas.nro_doc', 'ombu_personas.tel_movil',
                    DB::connection('mysql')->raw("CONCAT(ombu_personas.nombre, ' ', ombu_personas.apellido) as nombreCompleto"), 'ombu_personas.email')
                ->where('ombu_personas.nro_doc', '=', $nro_doc)
                ->where('ombu_cc_prestamos.estado', '!=', 'CANC')
                ->where('ombu_cc_prestamos.estado', '!=', 'ANUL')
                ->first();
            if (isset($result)) {
                $totalQuota = DB::connection('mysql')->table('ombu_cc_cuotas')
                    ->where('prestamo_id', '=', $result->prestamo_id)
                    ->where('fecha_venc', '<=', date("Y-m-d"))
                    ->groupBy('prestamo_id')
                    ->sum('ombu_cc_cuotas.monto_cuota');

                $totalMora = DB::connection('mysql')->table('ombu_cc_cuotas')
                    ->where('prestamo_id', '=', $result->prestamo_id)
                    ->where('fecha_venc', '<=', date("Y-m-d"))
                    ->groupBy('prestamo_id')
                    ->sum('ombu_cc_cuotas.monto_mora');

                $payment = DB::connection('mysql')->table('pagos')
                    ->where('documento', '=', $nro_doc)
                    ->where('estado', '=', 'Activo')
                    ->sum('pagos.monto');
                $payment = (int)$payment;

                $totalMountQuota = (int)$totalQuota + (int)$totalMora;
                $balance = $totalMountQuota - $payment;

                return response()->json([
                    'message' => 'result check credit by document number',
                    'data' => [
                        'credit_user_info' => $result,
                        'monto_mora' => (int)$totalMora,
                        'monto_cuota' => (int)$totalQuota,
                        'total_monto' => $totalMountQuota,
                        'pagos' => $payment,
                        'balance' => max($balance, 0)
                    ],
                    'status' => true,
                    'code' => 200
                ]);
            }

            return response()->json([
                'message' => 'El número de identificación ingresado no registra un crédito con CrediTek. Por favor valide la información e intente nuevamente.',
                'status' => false,
                'code' => 404
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
}
