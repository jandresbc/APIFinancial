<?php
namespace App\Http\Integrations\Siigo\v2\Adapters;

use Illuminate\Support\Facades\Http;

class SellerAdapter
{
    public function __construct()
    {
        $this->ws_v2 = config("Siigo.siigo_api_token_url_v2");
        $this->time_out = config("Siigo.siigo_timeout_api");
    }

    public function getAll($token)
    {
        $endpoint = "{$this->ws_v2}/v1/users";

        $headers = ["Authorization" => $token];
        $response = Http::withHeaders($headers)
        ->timeout($this->time_out)
        ->get($endpoint);
        return $response;
    }
}
