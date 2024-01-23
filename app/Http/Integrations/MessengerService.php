<?php

namespace App\Http\Integrations;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JetBrains\PhpStorm\ArrayShape;

class MessengerService
{
    protected string $urlApiHab = 'https://api103.hablame.co/api';

    /**
     * @param $url / PATH SERVICE
     * @param $method /POST, PATCH, GET
     * @param null $data /BODY REQUEST
     * @throws GuzzleException
     * @throws \JsonException
     */
    protected function messengerRequest($url, $method, $data = null)
    {
        $options = [];
        $client = new Client();
        if ($method === 'POST' || $method === 'PATCH') {
            $options = array(
                "headers" => [
                    'Content-Type' => 'application/json',
                    'account' => '10024215',
                    'apiKey' => 'D6JLeU68Sm7iHoMVb2JGNEvs7bVnjN',
                    'token' => '60e87c1984e35a14bcab12d50b0518f7'
                ],
                "body" => $data
            );
        } else if ($method === 'GET') {
            $options = array(
                "headers" => [
                    'Content-Type' => 'application/json',
                    'account' => '10024215',
                    'apiKey' => 'D6JLeU68Sm7iHoMVb2JGNEvs7bVnjN',
                    'token' => '60e87c1984e35a14bcab12d50b0518f7'
                ],
            );
        }
        $res = $client->request($method,$this->urlApiHab . $url, $options);
        return json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \JsonException
     * @throws GuzzleException
     */
    #[ArrayShape(['message' => "string", 'result' => "mixed"])] public function sendMessageHab($data): array
    {
        $result = $this->messengerRequest('/sms/v3/send/marketing/bulk', 'POST', $data);

        return [
            'message' => 'success',
            'result' => $result
        ];
    }
}
