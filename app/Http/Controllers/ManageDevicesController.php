<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use App\Http\Services\TrustonicService;
use App\Http\Integrations\Nuovo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManageDevicesController extends Controller
{

    /**
     * @param $message
     * @param $data
     * @param string $service
     * @param string $type
     * @return void
     * @throws \JsonException
     */
    protected function CreateLog ($message, $data, string $service = 'nuovo', string $type = 'info'): void
    {
        $log = DB::connection('logs')->table('devices_log');

        $log->insert([
            'type' => $type,
            'type_service' => $service,
            'message' => $message,
            'imei' => $data['imei'] ?? 0,
            'device_id' => $data['device'] ?? 0,
            'data' => json_encode($data, JSON_THROW_ON_ERROR)
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function EnrolledDevice (Request $request): JsonResponse
    {
        $data = $request->all();
        try {
            $validation = Validator::make($request->all(), [
                'marca_id' => 'required|int',
                'imei' => 'required|int'
            ]);

            if ($validation->fails()) {
                $this->CreateLog('Missing data please rectify the information sent.', [
                    'requestData' => $request->all(),
                    'error' => $validation->errors()->getMessages()
                ], '', 'error');
                return $this->Error('Missing data please rectify the information sent.', $validation->errors()->getMessages(), 400);
            }
            $response = '';
            $service = '';

            switch ((int)$data['marca_id']) {
//                case 6:
//                case 7:
//                    // moto-safe
//                    break;
//                case 8:
//                    //soft-lock
//                    break;
                case 11:
                    $trustonic = new TrustonicService();
                    $response = $trustonic->register($data);
                    $service = 'trustonic';

                    if (!$response['status']) {
                        $this->CreateLog($response['errorMessage'], [
                            'errorFile' => $response['errorFile'],
                            'errorLine' => $response['errorLine'],
                            'imei' => $data['imei'],
                            'device' => $data['imei']
                        ], $service, 'error');
                        return $this->Error($response['message'], [
                            'errorMessage' => $response['errorMessage'],
                            'errorFile' => $response['errorFile'],
                            'errorLine' => $response['errorLine'],
                        ], $response['code']);
                    }

                    $dataLog = $response['data'];
                    $dataLog['imei'] = $data['imei'];
                    $dataLog['device'] = $data['imei'];
                    $this->CreateLog($response['message'], $dataLog, $service);
                    break;
                default:
                    $nuovo = new Nuovo();
                    $service = 'nuovo';

                    $response= $nuovo->register($data['imei']);

                    $dataLog = $response['data'];
                    $dataLog['imei'] = $data['imei'];
                    if (!$response['data']['validation']) {
                        $dataLog['device'] = $data['imei'];
                    }
                    $response['data'] = [
                        'message' => $dataLog['message'],
                        'imei' => $dataLog['imei'],
                        'validation' => $dataLog['validation'],
                        'qrcode' => $dataLog['qrcode']
                    ];
                    $this->CreateLog($response['message'], $dataLog, $service);

                    break;
            }

            if (!$response['status']) {
                $dataLog = $response['data'];
                $dataLog['imei'] = $data['imei'];
                $dataLog['device'] = $data['imei'];
                $this->CreateLog($response['message'], $dataLog, $service, 'error');
                return $this->Error($response['message'], $response['data'], $response['code']);
            }
            return $this->Ok($response['message'], $response['data']);
        } catch (Exception $e){
            $this->CreateLog($e->getMessage(), [
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
                'device' => $data['imei'],
                'imei' => $data['imei'],
            ], '', 'error');
            return $this->Error('A system error has occurred, the transaction has been denied, please try again later.', [
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws GuzzleException|\JsonException
     */
    public function ValidationDevice (Request $request): JsonResponse
    {
        $data = $request->all();

        try {
            $validation = Validator::make($request->all(), [
                'marca_id' => 'required|int',
                'imei' => 'required|int'
            ]);

            if ($validation->fails()) {
                $this->CreateLog('Missing data please rectify the information sent.', [
                    'requestData' => $request->all(),
                    'error' => $validation->errors()->getMessages()
                ], '', 'error');
                return $this->Error('Missing data please rectify the information sent.', $validation->errors()->getMessages(), 400);
            }
            $response = '';
            $service = '';

            switch ((int)$data['marca_id']) {
//                case 6:
//                case 7:
//                    // moto-safe
//                    break;
//                case 8:
//                    //soft-lock
//                    break;
                case 11:
                    $trustonic = new TrustonicService();
                    $response = $trustonic->getStatus($data['imei']);
                    $service = 'trustonic';
                    if (!$response['status']) {
                        return $this->Error($response['message'], [
                            'errorMessage' => $response['errorMessage'],
                            'errorFile' => $response['errorFile'],
                            'errorLine' => $response['errorLine'],
                        ], $response['code']);
                    }

                    $dataLog = $response['data'];
                    $dataLog['imei'] = $data['imei'];
                    $dataLog['device'] = $data['imei'];
                    $this->CreateLog($response['message'], $dataLog, $service);
                    break;
                default:
                    $nuovo = new Nuovo();

                    $response= $nuovo->validatePhone($data['imei']);
                    $service = 'nuovo';

                    if (!isset($response['data'])) {
                        return $this->Ok($response['message'], []);
                    }

                    $dataLog = $response['data'];
                    $dataLog['imei'] = $data['imei'];
                    $dataLog['device'] = $data['imei'];
                    $this->CreateLog($response['message'], $dataLog, $service);

                    break;
            }
            if (!$response['status']) {
                $dataLog = $response['data'];
                $dataLog['imei'] = $data['imei'];
                $dataLog['device'] = $data['imei'];
                $this->CreateLog($response['message'], $dataLog, $service, 'error');
                return $this->Error($response['message'], $response['data'], $response['code']);
            }
            return $this->Ok($response['message'], $response['data'] ?? []);
        } catch (Exception $e){
            $this->CreateLog($e->getMessage(), [
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
                'device' => $data['imei'],
                'imei' => $data['imei'],
            ], '', 'error');
            return $this->Error('A system error has occurred, the transaction has been denied, please try again later.', [
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
            ], 500);
        }
    }
}
