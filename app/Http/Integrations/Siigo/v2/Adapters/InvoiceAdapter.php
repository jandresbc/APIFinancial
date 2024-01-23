<?php
namespace App\Http\Integrations\Siigo\v2\Adapters;

use Illuminate\Support\Facades\Http;
class InvoiceAdapter
{
    public function __construct()
    {
        $this->ws_v2 = config("Siigo.siigo_api_token_url_v2");
        $this->time_out = config("Siigo.siigo_timeout_api");
    }

    public function list(String $token, $page = 1, $page_size = 100)
    {
        $endpoint = "{$this->ws}/v1/invoices";
        $params['page'] = $page;
        $params['page_size'] = $page_size;
        $headers = ["Authorization" => $token];
        $response = Http::withHeaders($headers)
        ->timeout($this->time_out)
        ->retry(5, 10)
        ->get($endpoint, $params);
        return $response;
    }

    public function getById(String $token, $id)
    {
        $endpoint = "{$this->ws_v2}/v1/invoices/{$id}";
        $headers = ["Authorization" => $token];
        dd($endpoint, $headers);
        $response = Http::withHeaders($headers)
            ->timeout($this->time_out)
            ->get($endpoint);
        return $response;
    }

    public function searchByParam( String $token, $params)
    {
        try {
            $endpoint = "{$this->ws}/v1/invoices";
            $headers = [
                "Authorization" => $token,
            ];
            $response = Http::withHeaders($headers)
                ->timeout($this->time_out)
                ->retry(10, 1000)
                ->get($endpoint, $params);
            return $response;
        } catch (\Throwable $th) {
            die($th->getMessage());
        }

    }

    public function getType($docClass, $docCode, String $token)
    {
        $endpoint = "{$this->ws}/ERPDocumentTypes/GetByCode";
        $params['docClass'] = $docClass;
        $params['docCode'] = $docCode;
        $params['namespace'] = $this->namespace;
        $headers = [
            "Authorization" => $token,
            "Ocp-Apim-Subscription-Key" => $this->oask
        ];
        $response = Http::withHeaders($headers)
            ->timeout($this->time_out)
            ->get($endpoint, $params);
        return $response;
    }

    public function create($params, String $token)
    {
        $endpoint = "{$this->ws_v2}/v1/invoices";
        $headers = [
            "Authorization" => $token,
            "Content-Type" => "application/json",
        ];
        $response = Http::withHeaders($headers)
            ->timeout($this->time_out)
            ->retry(2, 1000)
            ->post($endpoint, $params);
        return $response;
    }

    public function download(String $token, String $hash_id)
    {
        $endpoint = "{$this->ws_v2}/v1/invoices/{$hash_id}/pdf";
        $headers = ["Authorization" => $token];
        $response = Http::withHeaders($headers)
        ->timeout($this->time_out)
        ->retry(20, 1000)
        ->get($endpoint);
        return $response;
    }
}
