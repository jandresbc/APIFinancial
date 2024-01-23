<?php
namespace App\Http\Integrations\Siigo\v2\Adapters;

use Illuminate\Support\Facades\Http;

class DocumentTypeAdapter
{
    public function __construct()
    {
        $this->ws_v2 = config("Siigo.siigo_api_token_url_v2");
        $this->time_out = config("Siigo.siigo_timeout_api");
    }

    public function getAllByType($token, $type = 'FV')
    {
        // /v1/document-types?type=FV
        $endpoint = "{$this->ws_v2}/v1/document-types";
        $params['type'] = $type;
        $headers = ["Authorization" => $token];

        $response = Http::withHeaders($headers)
        ->timeout($this->time_out)
        ->retry(5, 10)
        ->get($endpoint, $params);
        return $response;
    }

        // $endpoint = "{$this->ws}/ERPDocumentTypes/GetAll";

        // $params['numberPage'] = $page;
        // $params['namespace'] = $this->namespace;

        // $headers = ["Authorization" => $token,
        //             "Ocp-Apim-Subscription-Key" => $this->oask];
        // $response = Http::withHeaders($headers)
        // ->timeout($this->time_out)
        // ->get($endpoint, $params);
        // return $response;

    public function getAvailables($token)
    {
        $endpoint = "{$this->ws}/ERPDocumentTypes/GetAvailablesERPDocumentType";

        $params['namespace'] = $this->namespace;

        $headers = ["Authorization" => $token,
                    "Ocp-Apim-Subscription-Key" => $this->oask];
        $response = Http::withHeaders($headers)
        ->timeout($this->time_out)
        ->get($endpoint, $params);
        return $response;
    }

    public function getByCode($token, $docClass, $docCode)
    {
        $endpoint = "{$this->ws}/ERPDocumentTypes/GetByCode";

        $params['docCode'] = $docCode;
        $params['docClass'] = $docClass;
        $params['namespace'] = $this->namespace;

        $headers = ["Authorization" => $token,
                    "Ocp-Apim-Subscription-Key" => $this->oask];
        $response = Http::withHeaders($headers)
        ->timeout($this->time_out)
        ->get($endpoint, $params);
        return $response;
    }

    public function getByID($token, $id)
    {
        $endpoint = "{$this->ws}/ERPDocumentTypes/GetByID/".$id;

        $params['namespace'] = $this->namespace;

        $headers = ["Authorization" => $token,
                    "Ocp-Apim-Subscription-Key" => $this->oask];
        $response = Http::withHeaders($headers)
        ->timeout($this->time_out)
        ->get($endpoint, $params);
        return $response;
    }

}
