<?php

namespace App\Http\Integrations\RapiLoan\v1\Adapters;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;

class RapiLoanAdapter
{
    
    public function __construct()
    {
        $this->apiUrl = config("rapiLoan.api_url");
    }

    public function getCashin($id, $channel)
    {
        $id = strlen($id) < 7 ? str_pad($id, 7, "0", STR_PAD_LEFT) : $id;

        try {
            $query = [
                "id_clave" => $id,
                "cod_trx" => $id,
                "canal" => $channel,
                "fecha_hora_operacion" => Carbon::now()->format("Y-m-d H:i:s")
            ];

            $client = new Client(['base_uri' => $this->apiUrl]);
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($query, JSON_THROW_ON_ERROR),
            ];
            $res = $client->request('POST', 'rapipago/api/cashin/consulta', $options);
            return json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR);

        } catch (Exception $th) {
            dd($th);
        }
    }

    public function getCashinPay($id_number, $document, $barra, $amount, $date, $channel)
    {
        try {
            $query = [
                "id_numero" => $id_number,
                "cod_trx" => $document,
                "barra" => $barra,
                "canal" => $channel,
                "importe" => $amount,
                "fecha_hora_operacion" => $date
            ];
            $client = new Client(['base_uri' => $this->apiUrl]);
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($query, JSON_THROW_ON_ERROR),
            ];

            $res = $client->request('POST', 'rapipago/api/cashin/pago', $options);
            return json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR);

        } catch (Exception $th) {
            dd($th);
        }
    }
}
