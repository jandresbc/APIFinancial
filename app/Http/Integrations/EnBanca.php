<?php

namespace App\Http\Integrations;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Foundation\Mix;

class EnBanca
{
    protected string $token = '';
    protected string $apiUrl = 'https://api.mycallscout.com/ub/api/v2/';

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function __construct() {
        $this->login();
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws \JsonException
     */
    protected function login (): void
    {
        $credentials = [
            'username' => 'creditek',
            'password' => 'tRY^c80jk5oY#yC@wKxNP'
        ];

        $client = new Client(['base_uri' => $this->apiUrl]);
        $options = [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($credentials, JSON_THROW_ON_ERROR),
        ];

        $res = $client->request('POST','auth/login', $options);

        $response = json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->token = $response['token'];
    }

    /**
     * @param $phone
     * @param $document
     * @return array|mix
     * @throws GuzzleException
     */
    public function validationNumber ($phone, $document): array|mix
    {
        try {
            $client = new Client(['base_uri' => $this->apiUrl]);
            $options = [
                'headers' => [
                    'accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'bearer ' . $this->token
                ],
                'body' => json_encode([
                    'documentType' => 'CC',
                    'documentNumber' => $document,
                    'phoneNumber' => $phone
                ], JSON_THROW_ON_ERROR),
            ];

            $res = $client->request('POST','services/contact/match/phone', $options);

            return [
                'status' => true,
                'data' => json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR)
            ];

        } catch (Exception $e) {
            return [
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ];
        }
    }
}
