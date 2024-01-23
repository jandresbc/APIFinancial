<?php

namespace App\Http\Integrations;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Nuovo
{
    protected string $urlApi = 'https://app.nuovopay.com/dm/api/';
   protected string $token = '6efca0d204724b9da472eb0faa07ef3d';
    // protected string $token = 'ced60d6615ba43ecba904432d56469e4';

    /**
     * @param $method
     * @param $uri
     * @param null $data
     * @param string $contentType
     * @return mixed
     * @throws GuzzleException
     * @throws \JsonException
     */
    protected function requestNuovo($method, $uri, $data = null, string $contentType = 'application/json'): mixed
    {
        $client = new Client(['base_uri' => $this->urlApi]);
        $options = [
            'headers' => [
                'Authorization' => 'Token ' . $this->token,
                'Accept' => $contentType,
                'Content-Type' => $contentType
            ]
        ];

        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH' || isset($data)) {
            $options['body'] = $data;
        }

        $result = $client->request($method, $uri, $options);

        return json_decode($result->getBody(), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Service realice locked phone in nuovoPay platform
     * @param $device_id
     * @return array
     */
    public function lockPhone($device_id): array
    {
        try {
            $data = json_encode([
                'device_ids[]' => $device_id
            ], JSON_THROW_ON_ERROR);
            $response = $this->requestNuovo('PATCH', 'v1/devices/lock.json', $data);

            return [
                'status' => true,
                'code' => 200,
                'message' => 'phone is blocked',
                'data' => $response
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
        } catch (GuzzleException $e) {
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
     * this service realice unlock phone in nuovoPay platform
     * @param $device_id
     * @param $setDateLock
     * @return array
     */
    public function unlockPhone($device_id, $setDateLock = null): array
    {
        try {
            if (isset($setDateLock)) {
                $data = json_encode([
                        'device_ids' => [$device_id],
                        'auto_lock_date' => $setDateLock
                    ], JSON_THROW_ON_ERROR);
            } else {
                $data = json_encode([
                    'device_ids' => [$device_id]
                ], JSON_THROW_ON_ERROR);
            }

            $response = $this->requestNuovo('PATCH', 'v2/devices/unlock.json', $data);

            return [
                'status' => true,
                'code' => 200,
                'message' => 'phone is unlocked',
                'data' => $response
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
        } catch (GuzzleException $e) {
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

    public function validatePhone($imei): array
    {
        try {
            $response = $this->requestNuovo('GET', 'v1/devices.json?search_string=' . $imei);
            if (isset($response->devices[0])) {
                $device = $response->devices[0];
                $validation = false;

                if($device->status == "enrolled" && $device->eula_status = "Accepted" && $device->getting_started_button_clicked == true) {
                    $validation = true;
                }else{
                    $validation = false;
                }
                
                return match ($validation) {
                    true => [
                        'status' => true,
                        'code' => 200,
                        'message' => 'success',
                        'data' => [
                            'message' => 'phone is register success, we are enrolled.',
                            'validation' => true
                        ]
                    ],
                    default => [
                        'status' => true,
                        'code' => 200,
                        'message' => 'success',
                        'data' => [
                            'message' => 'phone is register but not enrolled',
                            'validation' => false
                        ]
                    ],
                };
            }
            return [
                'status' => true,
                'code' => 200,
                'message' => 'Device with imei ' . $imei . ' is not registered.',
            ];
        } catch (Exception $e) {
            return [
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'data' => [
                    'errorMessage' => $e->getMessage(),
                    'errorFile' => $e->getFile(),
                    'errorLine' => $e->getLine(),
                ],
                'code' => 500,
                'status' => false
            ];
        } catch (GuzzleException $e) {
            return [
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'data' => [
                    'errorMessage' => $e->getMessage(),
                    'errorFile' => $e->getFile(),
                    'errorLine' => $e->getLine(),
                ],
                'code' => 500,
                'status' => false
            ];
        }
    }

    public function register($imei): array
    {
        try {
            $data = json_encode([
                'device' => [
                    'type' => 'AndroidDevice',
                    'imei_no' => (string)$imei,
                ]
            ], JSON_THROW_ON_ERROR);
            $response = $this->requestNuovo('POST', 'v1/devices/register.json', $data);
            if ($response->success) {
                $qrcode = file_get_contents("../resources/imgs/QrNuovo.png");
                $imgbase64 = base64_encode($qrcode);
                
                return [
                    'status' => true,
                    'code' => 200,
                    'message' => 'success',
                    'data' => [
                        'message' => $response->success_message,
                        'imei' => $imei,
                        'device' => $response->device_id,
                        'validation' => true,
                        'qrcode' => $imgbase64
                    ]
                ];
            }
            return [
                'status' => true,
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'message' => 'Device is not register',
                    'status_register' => $response->status,
                    'imei' => $imei,
                    'validation' => false
                ]
            ];
        } catch (Exception $e) {
            return [
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'data' => [
                    'errorMessage' => $e->getMessage(),
                    'errorFile' => $e->getFile(),
                    'errorLine' => $e->getLine(),
                ],
                'code' => 500,
                'status' => false
            ];
        } catch (GuzzleException $e) {
            return [
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'data' => [
                    'errorMessage' => $e->getMessage(),
                    'errorFile' => $e->getFile(),
                    'errorLine' => $e->getLine(),
                ],
                'code' => 500,
                'status' => false
            ];
        }
    }
}
