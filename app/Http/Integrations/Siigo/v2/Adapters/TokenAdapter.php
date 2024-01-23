<?php
namespace App\Http\Integrations\Siigo\v2\Adapters;

use Illuminate\Support\Facades\Http;

class TokenAdapter
{

    public function __construct()
    {
        $this->ws_v2 = config("Siigo.siigo_api_token_url_v2");
        $this->time_out = config("Siigo.siigo_timeout_api");
    }

    public function get()
    {
        $endpoint = "{$this->ws_v2}/auth";
        $params['username'] = config('Siigo.siigo_user_name_v2');
        $params['access_key'] = config('Siigo.siigo_access_key_v2');
        $headers = ["Accept" => "application/json"];
        $response = Http::withHeaders($headers)
        ->timeout($this->time_out)
        ->post($endpoint, $params);

        return $response;
    }
}
