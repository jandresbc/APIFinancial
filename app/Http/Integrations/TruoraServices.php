<?php

namespace App\Http\Integrations;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class TruoraServices
{
    protected $urlApiAccount = 'https://api.account.truora.com/v1/';
    protected $urlApiIdentity = 'https://api.identity.truora.com/v1/';
    protected $apiKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhY2NvdW50X2lkIjoiIiwiYWRkaXRpb25hbF9kYXRhIjoie30iLCJjbGllbnRfaWQiOiJUQ0k0NmExYzQxZTY1YTkxNGEyNzdjZTg5MmU2ZTIyNzU1MSIsImV4cCI6MzIxNzE2MjUzNCwiZ3JhbnQiOiIiLCJpYXQiOjE2NDAzNjI1MzQsImlzcyI6Imh0dHBzOi8vY29nbml0by1pZHAudXMtZWFzdC0xLmFtYXpvbmF3cy5jb20vdXMtZWFzdC0xX09aRkNkUUVDMiIsImp0aSI6ImI3ZTJmZGI0LThjYWItNDMxOC1iNjllLWVmZTk1MGFlYWE2YyIsImtleV9uYW1lIjoiY3JlZGl0ZWsiLCJrZXlfdHlwZSI6ImJhY2tlbmQiLCJ1c2VybmFtZSI6ImNyZWRpdGVrLWNyZWRpdGVrIn0._DzmXUmWfREj0xAuPgV46kKP_EUUC6KRcRJckJ3lsFM';

    /**
     * @throws \JsonException
     * @throws GuzzleException
     */
    public function getValidationLink ()
    {
        $client = new Client();
        $options = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Truora-API-Key' => $this->apiKey
            ],
            'form_params' => [
                'key_type' => 'web',
                'grant' => 'digital-identity',
                'api_key_version' => 1,
                'country' => 'CO',
                'redirect_url' => 'https://creditek.com.co',
                'flow_id' => 'IPFeba9f124d6aedd9fd8038b8f3ef5502e'
            ],
        ];

        $res = $client->request('POST',$this->urlApiAccount . 'api-keys', $options);

        return json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \JsonException
     * @throws GuzzleException
     */
    public function getStatusValidation ($process_id)
    {
        $client = new Client();
        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Truora-API-Key' => $this->apiKey
            ]
        ];

        $res = $client->get($this->urlApiIdentity . 'processes/'. $process_id .'/result', $options);

        return json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

}
