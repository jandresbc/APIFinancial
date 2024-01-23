<?php

namespace App\Http\Integrations;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use DateTime;
use DateInterval;
use Exception;

class Trustonic
{
    private string $urlApi = 'https://api.creditek.cloud.trustonic.com/api/';
    private string $token = '';

    /**
     * @throws \JsonException
     */
    public function __construct()
    {
        $this->buildToken();
    }

    /**
     * @param $text
     * @return array|string
     */
    protected function base64UrlEncode($text): array|string
    {
        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($text)
        );
    }

    /**
     * @return void
     * @throws \JsonException
     */
    protected function buildToken(): void
    {
        $expired = new DateTime();
        $expired->add(new DateInterval('PT1H'));

        $pKeyId = openssl_pkey_get_private('file://' . app_path() . '/Keys/trustonic/private.pem');
//        dd($pKeyId);
//        puKeyID = openssl_pkey_get_details('file://'. app_path() . '\Keys\trustonic\public.pem')['key'];

        $headers = $this->base64UrlEncode(json_encode([
            "alg" => "RS256",
            "typ" => "JWT",
            "kid" => "creditek"
        ], JSON_THROW_ON_ERROR));

        $payload = $this->base64UrlEncode(json_encode([
            'exp' => strtotime($expired->format('Y-m-d H:i:s')),
            'client_id' => 'creditek',
            'scope' => [
                'devices:delete',
                'devices:update',
                'devices:status',
                'devices:getpin',
                'devices:register',
            ]
        ], JSON_THROW_ON_ERROR));


        openssl_sign("${headers}.${payload}", $signature, $pKeyId, "sha256WithRSAEncryption");
//        $signature = hash_hmac('sha256', "${headers}.${payload}", openssl_digest($pKeyId, 'SHA256', true));
        $base64UrlSignature = $this->base64UrlEncode($signature);

        $jwt = "$headers.$payload.$base64UrlSignature";
        $this->token = $jwt;
    }

    /**
     * @param $method
     * @param $uri
     * @param null $data
     * @param string $contentType
     * @return array
     * @throws GuzzleException
     */
    protected function request($method, $uri, $data = null, string $contentType = 'application/json'): array
    {
        try {
            $client = new Client(['base_uri' => $this->urlApi]);
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => $contentType,
                    'Content-Type' => $contentType
                ]
            ];

            if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH' || isset($data)) {
                $options['body'] = json_encode($data, JSON_THROW_ON_ERROR);
            }

            $result = $client->request($method, $uri, $options);

            return [
                'status' => true,
                'data' => json_decode($result->getBody(), false, 512, JSON_THROW_ON_ERROR)
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

    /**
     * @param $imei
     * @return array
     * @throws GuzzleException
     */
    public function getStatus($imei): array
    {
        try {
            return $this->request('POST', 'public/v1/devices/status', [
                'devices' => [
                    [
                        'imei' => $imei
                    ]
                ],
            ]);
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

    /**
     * @param $data
     * @return array
     * @throws GuzzleException
     */
    public function register($data): array
    {
        try {
            return $this->request('POST', 'public/v1/devices/register', [
                'devices' => [
                    [
                        'imei' => $data['imei'],
                        'assignedPolicy' => 'A11Financed',
                    ]
                ],
            ]);
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

    /**
     * @param $data
     * @return array
     * @throws GuzzleException
     */
    public function update($data): array
    {
        try {
            return $this->request('POST', 'public/v1/devices/update', [
                'devices' => [
                    [
                        'imei' => $data['imei'],
                        'assignedPolicy' => $data['polity'],
                    ]
                ],
            ]);
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

    /**
     * @param $data
     * @return array
     * @throws GuzzleException
     */
    public function getPin($data): array
    {
        try {
            return $this->request('POST', 'public/v1/devices/getPin', [
                'imei' => $data['imei'],
                'challenge' => $data['challenge'],
            ]);
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

    /**
     * @param $data
     * @return array
     * @throws GuzzleException
     */
    public function delete($data): array
    {
        try {
            return $this->request('POST', 'public/v1/devices/delete', [
                'devices' => [
                    [
                        'imei' => $data['imei'],
                    ]
                ],
            ]);
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
