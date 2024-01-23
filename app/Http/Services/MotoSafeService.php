<?php

namespace App\Http\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Http\Integrations\MotoSafe;
use JsonException;
use DateTime;
use DateInterval;

class MotoSafeService
{

    /**
     * @throws JsonException
     */
    public function createOrder ($data): array
    {
        $motoSafe = new MotoSafe();
        try {

            $result = $motoSafe->createOrder($data);

            if (!$result['status']) {
                $this->createLog($result['errorMessage'],'error', [
                    'errorFile' => $result['errorFile'],
                    'errorLine' => $result['errorLine']
                ]);
                return $result;
            }
            $this->createLog('Creación realizada correctamente', 'info', $data);
            $register = $motoSafe->getOrderByImei($data['imei']);
            $register['data']['imei'] = $data['imei'];
            if (count($register['data']['items']) === 0) {
                $this->createLog('unsuccessful registration', 'info', $register['data']);
                return [
                    'status' => false,
                    'code' => 400,
                    'message' => 'unsuccessful registration.',
                    'data' => $register['data']
                ];
            }
            $dataRegister = $register['data']['items'][0];
            $dataLog = $result['data'];
            $dataLog['register_data'] = $dataRegister;
            $dataLog['order_id'] = $dataRegister['id'];
            $dataLog['imei'] = $dataRegister['imei1'];
            $dataLog['status'] = $dataRegister['active'] ? 'enrolled':'registered';
            $this->createLog($dataLog['message'], 'info', $dataLog);
            return $result;

        } catch (Exception $e) {
            $this->createLog($e->getMessage(),'error', [
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
            ]);
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
     * @throws JsonException
     */
    public function createOrderOld ($data): array
    {
        $motoSafe = new MotoSafe();
        try {
            $credit = DB::connection('mysql')->table('ombu_cc_prestamos')
                ->join('ombu_personas', 'ombu_cc_prestamos.persona_id', '=', 'ombu_personas.id')
                ->where('ombu_cc_prestamos.estado', '!=', 'CANC')
                ->where('ombu_cc_prestamos.estado', '!=', 'ANUL')
                ->where('ombu_personas.nro_doc', '=', $data['document'])
                ->select('ombu_cc_prestamos.id')
                ->first();
            $request = DB::connection('mysql')->table('ombu_solicitudes')
                ->where('estado_solicitud', '!=', 'CANC')
                ->where('estado_solicitud', '!=', 'ANUL')
                ->where('data', 'LIKE', '%"nro_doc": "'.$data['document'].'"%')
                ->select('id', 'data')
                ->first();
            if (!isset($request)) {
                return [
                    'message' => 'Request must exist in order to use this service.',
                    'code' => 400,
                    'status' => false
                ];
            }
            if (!isset($credit)) {
                return [
                    'message' => 'Credit must exist in order to use this service.',
                    'code' => 400,
                    'status' => false
                ];
            }
            $quota = DB::connection('mysql')->table('ombu_cc_cuotas')
                ->where('prestamo_id', '=', $credit->id)
                ->where('cuota_nro', '=', '1')
                ->first();
            $mountQuota = ($quota->monto_cuota ?? 0) + ($quota->monto_mora ?? 0);
            $newTime = new DateTime();
            $dueDate = new DateTime($quota->fecha_venc ?? 'now');
            $data['dueDate'] = $dueDate->format('Y-m-d'). 'T' . $newTime->format('H:i:s') . 'Z';
            $data['payAmount'] = $mountQuota;
            $dataRequest = json_decode($request->data, true, 512, JSON_THROW_ON_ERROR);
            $data['numberOfInstallments'] = (int)$dataRequest['cantidad_cuotas'];

            $res = $motoSafe->createOrder($data);
            if (!$res['status']) {
                $this->createLog($res['errorMessage'],'error', [
                    'errorFile' => $res['errorFile'],
                    'errorLine' => $res['errorLine']
                ]);
                return $res;
            }
            $this->createLog('Creación realizada correctamente', 'info', $data);
            $this->createLog('Respuesta de creación', 'info', $res['data']);
            return $res;

        } catch (Exception $e) {
            $this->createLog($e->getMessage(),'error', [
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
            ]);
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
     * @throws JsonException
     */
    public function getOrderByImei ($imei): array
    {
        $motoSafe = new MotoSafe();

        try {
            $res = $motoSafe->getOrderByImei($imei);
            if (!$res['status']) {
                $this->createLog($res['errorMessage'],'error', [
                    'errorFile' => $res['errorFile'],
                    'errorLine' => $res['errorLine']
                ]);
            }
            $this->createLog('Consulta realizada correctamente por imei', 'info', $imei);
            $this->createLog('Respuesta de consulta', 'info', $res['data']);
            return $res;
        } catch (Exception $e) {
            $this->createLog($e->getMessage(),'error', [
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
            ]);
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
     * @return array
     * @throws JsonException
     */
    public function getOrders (): array
    {
        $motoSafe = new MotoSafe();

        try {
            $res = $motoSafe->getOrders();
            if (!$res['status']) {
                $this->createLog($res['errorMessage'],'error', [
                    'errorFile' => $res['errorFile'],
                    'errorLine' => $res['errorLine']
                ]);
            }
            $this->createLog('Respuesta de consulta', 'info', $res['data']['meta']);
            return $res;
        } catch (Exception $e) {
            $this->createLog($e->getMessage(),'error', [
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
            ]);
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
     * @param $message
     * @param string $type
     * @param $data
     * @return int
     * @throws JsonException
     */
    public function createLog ($message, string $type = 'info', $data = null): int
    {
        return DB::connection('logs')->table('moto_safe_logs')
            ->insertGetId([
                'message' => $message,
                'order_id' => $data['order_id'] ?? 0,
                'imei' => $data['imei'] ?? null,
                'type' => $type,
                'status' => $data['status'] ?? 'unregistered',
                'data' => json_encode($data, JSON_THROW_ON_ERROR)
            ]);
    }
}
