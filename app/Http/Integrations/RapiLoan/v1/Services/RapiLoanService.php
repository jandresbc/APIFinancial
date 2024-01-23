<?php

namespace App\Http\Integrations\RapiLoan\v1\Services;

use App\Http\Integrations\RapiLoan\v1\Adapters\RapiLoanAdapter;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class RapiLoanService
{
    protected RapiLoanAdapter $rapiLoanAdapter;

    public function __construct(RapiLoanAdapter $rapiLoanAdapter)
    {
        $this->rapiLoanAdapter = $rapiLoanAdapter;
    }

    public function getCashin($id, $channel)
    {
        isset($channel) ? $channel = $channel :$channel = config("rapiLoan.channel_undefined");
        try {
            return $this->rapiLoanAdapter->getCashin($id, $channel);
        } catch (Exception $th) {
            dd($th);
        }
    }

    public function getCashinPay($id_number, $document, $bar_code, $amount, $date, $channel)
    {
        $channel = $channel ?? config("rapiLoan.channel_undefined");
        try {
            return $this->rapiLoanAdapter->getCashinPay($id_number, $document, $bar_code, $amount, $date, $channel);
        } catch (\Exception $th) {
            dd($th);
        }
    }

    /**
     * @param $data
     * @param $type
     * @return bool
     * @throws \JsonException
     */
    public function uploadPayment ($data, $type): bool
    {
        if ($type === 'efecty') {
            $payment = $this->getCashin($data['document'], $type);
            if ((int)$payment['codigo_respuesta'] === 6) {
                return false;
            }
            $quotas = $payment['facturas'];
            $cashin = $this->getCashinPay($quotas[0]['id_numero'], $data['document'], $quotas[0]['barra'], $data['amount'], $data['datetime_payment'],$type);

            DB::connection('logs')->table('efecty_log')->insert([
                'user_id' => $data['user_id'],
                'datePayment' => $data['datetime_payment'],
                'document' => $data['document'],
                'message' => 'payment confirmation',
                'type' => 'info',
                'type_service' => 'payment',
                'mount' => $data['amount'],
                'barra' => $quotas[0]['barra'],
                'data' => json_encode([
                    $data,
                    'payment_status' => (int)$cashin['codigo_respuesta'] === 0
                ], JSON_THROW_ON_ERROR)
            ]);

            return (int)$cashin['codigo_respuesta'] === 0;
        }
        if ($type === 'wompi') {
            $payment = $this->getCashin($data['document'], $type);
            if ((int)$payment['codigo_respuesta'] === 6) {
                return false;
            }
            $quotas = $payment['facturas'];
            $cashin = $this->getCashinPay($quotas[0]['id_numero'], $data['document'], $quotas[0]['barra'], $data['amount'], $data['datetime_payment'],$type);

            DB::connection('logs')->table('wompi_log')->insert([
                'typeLog' => 'info',
                'message' => 'payment confirmation',
                'code_id' => $data['code_id'],
                'reference' => $data['reference'],
                'link_payment' => $data['link_payment'],
                'mount' => $data['amount'],
                'barra' => $quotas[0]['barra'],
                'data' => json_encode([
                    $data,
                    'payment_status' => (int)$cashin['codigo_respuesta'] === 0
                ], JSON_THROW_ON_ERROR)
            ]);

            return (int)$cashin['codigo_respuesta'] === 0;
        }

        return false;
    }
}
