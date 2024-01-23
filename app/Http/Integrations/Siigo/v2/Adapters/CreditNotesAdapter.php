<?php

namespace App\ServicesV2\AdaptersV2;
use Illuminate\Support\Facades\Http;

class CreditNotesAdapter
{
    public function __construct()
    {
        $this->ws = config("Siigo.siigo_api_token_url_v2");
        $this->time_out = config("Siigo.siigo_timeout");
    }

    public function create(array $params, String $token)
    {
        $endpoint = "{$this->ws}/v1/credit-notes";
        $headers = [
            'Content-Type' => 'application/json',
            "Authorization" => $token,
        ];
        dump($endpoint, $headers, json_encode($params));
        $response = Http::withHeaders($headers)
        ->timeout($this->time_out)
        ->retry(3, 100)
        ->post($endpoint, $params);
        return $response;
    }
}
