<?php

namespace App\Http\Integrations;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Foundation\Mix;

class MotoSafe
{
    protected string $urlApi = 'http://portal-dot-moto-alcatraz.appspot.com/';
    protected string $token = 'zqfwb86zabo6qrvpoqmzdf8k49nky9';
    protected string $accessToken = '';

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function __construct()
    {
        $this->getAccessToken();
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws \JsonException
     */
    protected function getAccessToken (): void
    {
        $client = new Client(['base_uri' => $this->urlApi]);
        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Api-Key' => $this->token
            ]
        ];

        $res = $client->request('POST','v2/api/finance/dealer/token', $options);

        $response = json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->accessToken = $response['token'];
    }

    /**
     * @param $method
     * @param $uri
     * @param null $data
     * @param string $contentType
     * @return array|mix
     * @throws GuzzleException
     */
    protected function request($method, $uri, $data = null, string $contentType = 'application/json'): array|mix
    {
        try {
            $client = new Client(['base_uri' => $this->urlApi, 'http_errors' => false]);
            $options = [
                'headers' => [
                    'Api-Key' => $this->token,
                    'Accept' => $contentType,
                    'Content-Type' => $contentType,
                    'Authorization' => 'bearer ' . $this->accessToken,
                ]
            ];

            if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH' || isset($data)) {
                $options['body'] = $data;
            }

            $result = $client->request($method, $uri, $options);

            return [
                'status' => true,
                'data' => json_decode($result->getBody(), true, 512, JSON_THROW_ON_ERROR)
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

    public function createOrder ($dataRequest): array
    {
        try {
            $data = json_encode([
                    'imei1' => $dataRequest['imei'],
                    'imei2' => 0,
                    'dueDate' => $dataRequest['dueDate'],
                    'currency' => 'COP',
                    'recurrency' => 15,
                    'payAmount' => (int)$dataRequest['payAmount'],
                    'numberOfInstallments' => $dataRequest['numberOfInstallments'],
                    'settings' => [
                        'force_Accept_Terms' => true
                    ],
                ], JSON_THROW_ON_ERROR);

            $res = $this->request('POST', 'v2/api/finance/dealer/orders', $data);

            if (!$res['status']) {
                return $res;
            }

            return [
                'status' => true,
                'data' => $res['data'],
                'code' => 200,
                'message' => 'success'
            ];

        } catch (Exception $e){
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

    public function getOrderByImei ($imei): array
    {
        try {
            $res =  $this->request('GET', 'v2/api/finance/dealer/orders?imeis='.$imei);

            if (!$res['status']) {
                return $res;
            }

            return [
                'status' => true,
                'data' => $res['data'],
                'code' => 200,
                'message' => 'success'
            ];

        } catch (Exception $e){
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

    public function getOrders (): array
    {
        try {
            $res =  $this->request('GET', 'v2/api/finance/dealer/orders');

            if (!$res['status']) {
                return $res;
            }

            return [
                'status' => true,
                'data' => $res['data'],
                'code' => 200,
                'message' => 'success'
            ];

        } catch (Exception $e){
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
}
