<?php

namespace App\Http\Services;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use App\Http\Integrations\Trustonic;
use Exception;

class TrustonicService extends Service
{
    /**
     * @throws \JsonException
     */
    public function createLog($message, $data, $type = null): void
    {
        DB::connection('logs')->table('trustonicLog')
            ->insert([
                'message' => $message,
                'type' => $type ?? 'info',
                'data' => json_encode($data, JSON_THROW_ON_ERROR)
            ]);
    }

    /**
     * @param $imei
     * @return array
     * @throws \JsonException|GuzzleException
     */
    public function getStatus($imei): array
    {
        try {
            $trustonic = new Trustonic();
            $response = $trustonic->getStatus($imei);
            if (!$response['status']) {
                $this->createLog($response['errorMessage'], $response, 'error');
                return $response;
            }

            $device = $response['data']->devices[0];
            $data = [];
            if ($device->deviceReady) {
                $data['message'] = 'device is enrolled';
                $data['validation'] = true;
            } else {
                $data['message'] = 'device register but not enrolled';
                $data['validation'] = false;
            }
            $response['data']->extraData = $data;

            $this->createLog('Consulta realizada correctamente', $response['data']);

            return $this->Ok('success', $data);
        } catch (Exception $e) {
            $data = [
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine()
            ];
            $this->createLog($e->getMessage(), $data, 'error');
            return $this->Error('A system error has occurred, the transaction has been denied, please try again later.', [
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
            ]);
        }
    }

    /**
     * @param $data
     * @return array
     * @throws \JsonException
     * @throws GuzzleException
     */
    public function register($data): array
    {
        try {
            $trustonic = new Trustonic();
            $response = $trustonic->register($data);
            if (!$response['status']) {
                $this->createLog($response['errorMessage'], $response, 'error');
                return $response;
            }
            $this->createLog('Registro realizado correctamente', $response['data']);

            $dataR = $response['data']->devices[0];

            if (isset($dataR->error)) {
                return $this->Ok('success', [
                    'message' => $dataR->error->error,
                    'imei' => $data['imei'],
                    'validation' => false
                ]);
            }

            return $this->Ok('success', [
                'imei' => $data['imei'],
                'validation' => true
            ]);

        } catch (Exception $e) {
            $data = [
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine()
            ];
            $this->createLog($e->getMessage(), $data, 'error');
            return $this->Error('A system error has occurred, the transaction has been denied, please try again later.', [
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
            ]);
        }
    }

    /**
     * @param $data
     * @return array
     * @throws \JsonException
     * @throws GuzzleException
     */
    public function update($data): array
    {
        try {
            $trustonic = new Trustonic();
            $response = $trustonic->update($data);
            if (!$response['status']) {
                $this->createLog($response['errorMessage'], $response, 'error');
                return $response;
            }
            $this->createLog('Actualización realizada correctamente', $response['data']);

            return [
                'message' => 'success',
                'status' => true,
                'code' => 200,
                'data' => $response['data']
            ];

        } catch (Exception $e) {
            $data = [
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine()
            ];
            $this->createLog($e->getMessage(), $data, 'error');
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
     * @throws \JsonException
     * @throws GuzzleException
     */
    public function getPin($data): array
    {
        try {
            $trustonic = new Trustonic();
            $response = $trustonic->getPin($data);
            if (!$response['status']) {
                $this->createLog($response['errorMessage'], $response, 'error');
                return $response;
            }
            $this->createLog('Se obtuvo el pin correctamente', $response['data']);

            return [
                'message' => 'success',
                'status' => true,
                'code' => 200,
                'data' => $response['data']
            ];

        } catch (Exception $e) {
            $data = [
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine()
            ];
            $this->createLog($e->getMessage(), $data, 'error');
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
     * @throws \JsonException
     * @throws GuzzleException
     */
    public function delete($data): array
    {
        try {
            $trustonic = new Trustonic();
            $response = $trustonic->delete($data);
            if (!$response['status']) {
                $this->createLog($response['errorMessage'], $response, 'error');
                return $response;
            }
            if (isset($response['data']['devices'][0]['error'])) {
                $this->createLog('Error en el proceso de eliminación.', $response['data'], 'error');
                return [
                    'message' => 'Error in process delete device',
                    'status' => false,
                    'code' => 400,
                    'data' => $response['data']
                ];
            }
            $this->createLog('Se elimino el dispositivo correctamente', $response['data']);

            return [
                'message' => 'success',
                'status' => true,
                'code' => 200,
                'data' => $response['data']
            ];

        } catch (Exception $e) {
            $data = [
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine()
            ];
            $this->createLog($e->getMessage(), $data, 'error');
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
