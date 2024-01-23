<?php

namespace App\Http\Integrations;
use GuzzleHttp\Client;
use Exception;
use GuzzleHttp\Exception\GuzzleException;

class DataCredito
{
    protected string $urlDt = 'https://datacreditodev.creditek.com.co/api/';

    /**
     * @param $data
     * @return array
     * @throws GuzzleException
     */
    public function getDataCredito ($data): array
    {
        $client = new Client(['base_uri' => $this->urlDt]);

        try {
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'id_number' => $data['document'],
                    'last_name' => strtoupper($data['last_name'])
                ], JSON_THROW_ON_ERROR)
            ];


            $response = $client->request('GET', 'requests/hc/get', $options);

            return [
                'status' => true,
                'data' => json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR)
            ];
        } catch (Exception $e) {
            return [
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'data' => [
                    'errorMessage' => $e->getMessage(),
                    'errorFile' => $e->getFile(),
                    'errorLine' => $e->getLine(),
                ],
                'status' => false,
                'code' => 500
            ];
        }
    }
}
