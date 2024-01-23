<?php
namespace App\Http\Integrations\Siigo\v2\Adapters;

use Illuminate\Support\Facades\Http;

class PaymentAdapter
{
    public function __construct()
    {
        $this->ws_v2 = config("Siigo.siigo_api_token_url_v2");
        $this->time_out = config("Siigo.siigo_timeout_api");
    }

    public function getAll($token, $document_type= "FV")
    {
        $endpoint = "{$this->ws_v2}/v1/payment-types";

        $params['document_type'] = $document_type;

        $headers = ["Authorization" => $token];
        $response = Http::withHeaders($headers)
        ->timeout($this->time_out)
        ->get($endpoint, $params);
        return $response;
    }
}
