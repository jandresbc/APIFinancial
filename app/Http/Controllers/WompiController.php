<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Integrations\Wompi;
use App\Http\Resources\Wompi\WompiPaymentLinkResource;
use App\Http\Resources\Wompi\WompiTransactionResource;
use App\Http\Integrations\Nuovo;
use App\Http\Services\TrustonicService;
use App\Http\Integrations\RapiLoan\v1\Services\RapiLoanService;
//Models
use App\Models\CrCore\Pagos;

use Carbon\Carbon;

class WompiController extends Controller
{

    protected RapiLoanService $rapiLoanService;
    public function __construct(RapiLoanService $rapiLoanService)
    {
        $this->rapiLoanService = $rapiLoanService;
    }

    /**
     * @param Request $request
     * @return JsonResponse|WompiPaymentLinkResource
     */
    public function paymentLink(Request $request): JsonResponse|WompiPaymentLinkResource
    {
        try {
            $wompi = new Wompi();

            $response = $wompi->getPaymentLink($request->name, $request->description, $request->amount);

            $response['data']['paymentLink'] = 'https://checkout.wompi.co/l/' . $response['data']['id'];

            $data = (object) $response['data'];

            return new WompiPaymentLinkResource($data);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al procesar la información, volverlo a intentarlo',
                'errorMessage' => $e->getMessage(),
                'errorCode' => $e->getCode(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }

    /**
     * @param $transaction_id
     * @return WompiTransactionResource|JsonResponse
     */
    public function getTransaction($transaction_id): WompiTransactionResource|JsonResponse
    {
        try{
            $wompi = new Wompi();
            $response = $wompi->getTrasactionWompi($transaction_id);

            $data = (object) $response->original['data']['data'];

            return new WompiTransactionResource($data);
        }catch(Exception $e){
            return response()->json([
                'message' => 'Error al procesar la información, volverlo a intentarlo',
                'errorMessage' => $e->getMessage(),
                'errorCode' => $e->getCode(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse|void
     * @throws \JsonException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function paymentEvent(Request $request)
    {
        try {
            $data = $request->all();
            $dataLog = [
                'code_id' => $data['data']['transaction']['id'],
                'reference' => $data['data']['transaction']['reference'],
                'link_payment' => $data['data']['transaction']['payment_method']['extra']['async_payment_url'] ?? 'sin link de referencia',
                'mount' => ($data['data']['transaction']['amount_in_cents']/100)
            ];

            $this->createBasicLog($data['data']['transaction']['status'], $data);
            if($data['data']['transaction']['status'] === 'APPROVED') {
                try {
                    $this->createLog($data['data']['transaction']['status'], $dataLog);
                    $ID = $data['data']['transaction']['customer_data']["legal_id"] ?? 0;
                    $monto = $data['data']['transaction']['amount_in_cents']/100;
                    $credit = DB::connection('mysql')->table('ombu_cc_prestamos')
                        ->join('ombu_personas', 'ombu_cc_prestamos.persona_id', '=', 'ombu_personas.id')
                        ->join('ombu_solicitudes', 'ombu_solicitudes.id', '=', 'ombu_cc_prestamos.solicitud_id')
                        ->where('ombu_cc_prestamos.estado', '!=', 'CANC')
                        ->where('ombu_cc_prestamos.estado', '!=', 'ANUL')
                        ->where('ombu_personas.nro_doc', '=', $ID)
                        ->select(
                            'ombu_cc_prestamos.id',
                            'ombu_cc_prestamos.nuovo_id',
                            'ombu_cc_prestamos.type_enrollment_id',
                            'ombu_solicitudes.data'
                        )
                        ->first();

                    $fechaPago = Carbon::parse($data['data']['transaction']['created_at'])->setTimezone('America/Bogota')->toDateTimeString();

                    $paymentExist = Pagos::where("fecha_hora_pago",$fechaPago)->where("monto",$monto)
                        ->where("plataforma","wompi")->where("documento",$ID)->exists();

                    if(!$paymentExist){
                        $payment = Pagos::create([
                            'fecha_hora_pago' => $fechaPago,
                            'monto' => $monto,
                            'documento' => $ID,
                            'plataforma' => 'wompi',
                            'pagador' => isset($credit) ? 'titular' : 'tercero',
                            'prestamo_id' => $credit->id ?? null,
                            'novedad' => (int)$ID === 0 ? 'El documento del cliente no se reconoció, Datos del cliente: ' . json_encode($data['data']['transaction']['customer_data'], JSON_THROW_ON_ERROR) : null,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);

                        if ((int)$ID !== 0) {
                            $statusLoan = DB::connection('mysql')->table('config_parametros')
                                ->where('grupo_id', '=', 24)
                                ->where('nombre', '=', 'status_loan')
                                ->where('estado', '=', 'HAB')
                                ->first();
                            if (isset($statusLoan) && $statusLoan->valor === 'active') {
                                $this->rapiLoanService->uploadPayment([
                                    'reference' => $dataLog['reference'],
                                    'amount' => $dataLog['mount'],
                                    'code_id' => $dataLog['code_id'],
                                    'link_payment' => $dataLog['link_payment'],
                                    'document' => $ID,
                                    'datetime_payment' => $fechaPago
                                ], 'wompi');
                            }
                        }
                        if (isset($credit) && $payment) {

                            $totalQuota = DB::connection('mysql')->table('ombu_cc_cuotas as oc')
                                ->join('ombu_cc_prestamos as ocp', 'ocp.id', '=', 'oc.prestamo_id')
                                ->where('oc.prestamo_id', '=', $credit->id)
                                ->where('oc.fecha_venc', '<=', date("Y-m-d",time()))
                                ->select('oc.*')
                                ->sum(DB::raw('oc.monto_cuota + IFNULL(oc.monto_mora,0)'));
                            $payments = DB::connection('mysql')->table('pagos')
                                ->where('documento', '=', $ID)
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
                                                $this->createBasicLog($result['message'], $result, 'error');
                                                return response()->json($result, $result['code']);
                                            }
                                        }
                                        break;
                                }
                            }
                        }

                        return response()->json([
                            'status' => true,
                            'code' => 200,
                            'message' => 'The payment was recorded in the table.'
                        ]);
                    }

                    return response()->json([
                        'status' => false,
                        'code' => 400,
                        'message' => 'The payment was not recorded in the table.'
                    ], 400);
                } catch (Exception $e) {
                    $this->createBasicLog($e->getMessage(), [
                        'errorLine' => $e->getLine(),
                        'errorFile' => $e->getFile(),
                    ], 'error');
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
        } catch (Exception $e) {
            $this->createBasicLog($e->getMessage(), [
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
            ], 'error');
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
     * @param $message
     * @param $data
     * @param string $type
     * @return void
     * @throws \JsonException
     */
    protected function createBasicLog($message, $data = null, string $type = 'info'):void
    {
        DB::connection('logs')->table('wompi_log')
            ->insert([
                'typeLog' => $type,
                'message' => $message,
                'data' => json_encode($data, JSON_THROW_ON_ERROR)
            ]);
    }

    /**
     * @param $message
     * @param $data
     * @param string $type
     * @return void
     * @throws \JsonException
     */
    protected function createLog($message, $data = null, string $type = 'info'):void
    {
        DB::connection('logs')->table('wompi_log')
            ->insert([
                'typeLog' => $type,
                'message' => $message,
                'code_id' => $data['code_id'],
                'reference' => $data['reference'],
                'link_payment' => $data['link_payment'],
                'mount' => $data['mount'],
                'data' => json_encode($data, JSON_THROW_ON_ERROR)
            ]);
    }
}
