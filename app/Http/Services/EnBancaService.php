<?php

namespace App\Http\Services;
use App\Http\Integrations\EnBanca;
use DateInterval;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Exception;
use DateTime;

class EnBancaService
{

    public function serviceActive ($phone, $document): array
    {
        $enBancaStatus = DB::connection('mysql')->table('config_parametros')
            ->where('grupo_id', '=', 22)
            ->where('nombre', '=', 'status_service')
            ->where('estado', '=', 'HAB')
            ->first();
        if (isset($enBancaStatus)) {
            if ((string)$enBancaStatus->valor === 'inactive') {
                return [
                    'message' => 'success',
                    'status' => true,
                    'code' => 200,
                    'data' => [
                        'document' => $document,
                        'phone' => $phone,
                        'validation' => true,
                        'message' => 'Success validation'
                    ]
                ];
            }
            return [
                'status' => false
            ];
        }
        return [
            'status' => false
        ];
    }

    /**
     * @param $data
     * @return array
     */
    public static function validate ($data):array
    {
        try {
            $enBanca = new self();
            $res = $enBanca->existValidation($data['phone'], $data['document']);
            if ($res) {
                return [
                    'message' => 'already exist validation with this phone number and document.',
                    'status' => true,
                    'code' => 200,
                    'data' => [
                        'document' => $res->document,
                        'phone' => $res->phone,
                        'validation' => filter_var($res->validation, FILTER_VALIDATE_BOOLEAN),
                        'message' => 'Validation already exist and due date is in ' . $res->dueDate
                    ]
                ];
            }

            return $enBanca->createValidation($data['phone'], $data['document']);

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
     * @param $phone
     * @param $document
     * @return object|bool
     */
    protected function existValidation ($phone, $document): object|bool
    {
        $date = new DateTime();
        $response = DB::connection('logs')->table('enbanca_validation_logs')
            ->where('document', '=', $document)
            ->where('phone', '=', $phone)
            ->where('dueDate', '>=', $date->format('Y-m-d'))
            ->first();
        $enBancaScore = DB::connection('mysql')->table('config_parametros')
            ->where('grupo_id', '=', 22)
            ->where('nombre', '=', 'score')
            ->where('estado', '=', 'HAB')
            ->first();
        if (isset($response, $enBancaScore)) {
            if ((int)$response->contactability < (int)$enBancaScore->valor) {
                DB::connection('logs')->table('enbanca_validation_logs')
                    ->where('document', '=', $document)
                    ->where('phone', '=', $phone)
                    ->where('dueDate', '>=', $date->format('Y-m-d'))
                    ->update([
                        'validation' => 'false'
                    ]);
                return DB::connection('logs')->table('enbanca_validation_logs')
                    ->where('document', '=', $document)
                    ->where('phone', '=', $phone)
                    ->where('dueDate', '>=', $date->format('Y-m-d'))
                    ->first();
            }

            return $response;
        }
        return false;
    }

    /**
     * @param $phone
     * @param $document
     * @return array
     * @throws \JsonException
     */
    protected function createValidation ($phone, $document): array
    {
        try {
            $enBanca = new EnBanca();
            $enBancaScore = DB::connection('mysql')->table('config_parametros')
                ->where('grupo_id', '=', 22)
                ->where('nombre', '=', 'score')
                ->where('estado', '=', 'HAB')
                ->first();

            $response = $enBanca->validationNumber($phone, $document);

            if (!$response['status']) {
                return $response;
            }

            if (!$response['data']['match']) {
                $enBancaLogId = $this->createLog('info', 'EnBanca service response match in false', json_encode([
                    'phone' => $phone,
                    'document' => $document,
                    $response['data']
                ], JSON_THROW_ON_ERROR));
                $data = [
                    'phone' => $phone,
                    'document' => $document,
                    'message' => 'EnBanca service response match in false.',
                    'validation' => 'false',
                    'enbanca_logs_id' => $enBancaLogId,
                    'match' => $response['data']['match'],
                    'hits' => $response['data']['hits'],
                    'active' => $response['data']['active'],
                    'firstSeen' => null,
                    'lastSeen' => null,
                    'contactability' => $response['data']['contactability'],
                    'dataRequest' => [
                        'phone' => $phone,
                        'document' => $document
                    ]
                ];
                $data['dataResponse'] = $response['data'];
                $this->createValidationLog($data);
                return [
                    'message'=> 'success',
                    'status' => true,
                    'data' => [
                        'validation' => false,
                        'message' => 'EnBanca service response match in false',
                        'phone' => $phone,
                        'document' => $document
                    ],
                    'code' => 200
                ];
            }
            if ((float)$response['data']['contactability'] < ($enBancaScore->valor ?? 40)) {
                $enBancaLogId = $this->createLog('info', 'Score in EnBanca service is much down', json_encode([
                    'phone' => $phone,
                    'document' => $document,
                    $response['data']
                ], JSON_THROW_ON_ERROR));
                $data = [
                    'phone' => $phone,
                    'document' => $document,
                    'message' => 'Score in EnBanca service is much down.',
                    'validation' => 'false',
                    'enbanca_logs_id' => $enBancaLogId,
                    'match' => $response['data']['match'],
                    'hits' => $response['data']['hits'],
                    'active' => $response['data']['active'],
                    'firstSeen' => $response['data']['firstSeen'],
                    'lastSeen' => $response['data']['lastSeen'],
                    'contactability' => $response['data']['contactability'],
                    'dataRequest' => [
                        'phone' => $phone,
                        'document' => $document
                    ]
                ];
                $data['dataResponse'] = $response['data'];
                $this->createValidationLog($data);

                return [
                    'message'=> 'success',
                    'status' => true,
                    'data' => [
                        'validation' => false,
                        'message' => 'score in EnBanca service is much down',
                        'phone' => $phone,
                        'document' => $document
                    ],
                    'code' => 200
                ];
            }

            $enBancaLogId = $this->createLog('info', 'Validation is true', json_encode([
                'phone' => $phone,
                'document' => $document,
                $response['data']
            ], JSON_THROW_ON_ERROR));
            $data = [
                'phone' => $phone,
                'document' => $document,
                'message' => 'Validation success in EnBanca service.',
                'validation' => 'true',
                'enbanca_logs_id' => $enBancaLogId,
                'match' => $response['data']['match'],
                'hits' => $response['data']['hits'],
                'active' => $response['data']['active'],
                'firstSeen' => $response['data']['firstSeen'],
                'lastSeen' => $response['data']['lastSeen'],
                'contactability' => $response['data']['contactability'],
                'dataRequest' => [
                    'phone' => $phone,
                    'document' => $document
                ]
            ];
            $data['dataResponse'] = $response['data'];
            $this->createValidationLog($data);

            return [
                'message'=> 'success',
                'status' => true,
                'data' => [
                    'validation' => true,
                    'message' => 'success validation',
                    'phone' => $phone,
                    'document' => $document
                ],
                'code' => 200
            ];


        } catch (Exception $e) {
            $this->createLog('error', $e->getMessage(), json_encode([
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine()
            ], JSON_THROW_ON_ERROR));
            return [
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ];
        } catch (GuzzleException $e) {
            $this->createLog('error', $e->getMessage(), json_encode([
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine()
            ], JSON_THROW_ON_ERROR));
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
     * @param $type
     * @param $message
     * @param $data
     * @return int
     */
    public function createLog ($type, $message, $data): int
    {
        return DB::connection('logs')->table('enbanca_logs')
            ->insertGetId([
                'message' => $message,
                'type' => $type,
                'data' => $data
            ]);
    }

    /**
     * @param $data
     * @return void
     * @throws \JsonException
     */
    public function createValidationLog ($data): void
    {
        $date = new DateTime();
        $dueDate = DB::connection('mysql')->table('config_parametros')
            ->where('grupo_id', '=', 22)
            ->where('nombre', '=', 'due_date')
            ->where('estado', '=', 'HAB')
            ->first();
        $extraDays = $dueDate->valor ?? 5;
        $date->add(new DateInterval("P{$extraDays}D"));

        DB::connection('logs')->table('enbanca_validation_logs')
            ->insertGetId([
                'enbanca_logs_id' => $data['enbanca_logs_id'],
                'message' => $data['message'],
                'document' => $data['document'],
                'phone' => $data['phone'],
                'validation' => $data['validation'],
                'match' => $data['match'],
                'hits' => $data['hits'],
                'active' => $data['active'],
                'firstSeen' => $data['firstSeen'],
                'lastSeen' => $data['lastSeen'],
                'contactability' => $data['contactability'],
                'dataRequest' => json_encode($data['dataRequest'], JSON_THROW_ON_ERROR),
                'dataResponse' => json_encode($data['dataResponse'], JSON_THROW_ON_ERROR),
                'dueDate' => $date->format('Y-m-d'),
            ]);
    }

    /**
     * @param $data
     * @return void
     * @throws \JsonException
     */
    public function createFileLog ($data): void
    {
        DB::connection('logs')->table('enbanca_file_logs')
            ->insertGetId([
                'enbanca_logs_id' => $data['enbanca_logs_id'],
                'people_id' => $data['id'],
                'message' => $data['message'],
                'document' => $data['document'],
                'phone' => $data['phone'],
                'data' => json_encode($data['data'], JSON_THROW_ON_ERROR)
            ]);
    }

    /**
     * @param $limit
     * @return array
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function generateFile($limit): array
    {
        try {
            set_time_limit(1200);
            $peoples = DB::connection('mysql')
                ->table('ombu_personas')
                ->orderBy('fecha_alta', 'DESC')
                ->limit($limit)
                ->get(['nombre', 'apellido', 'tel_movil', 'nro_doc', 'id']);
            $idException = [];
            foreach ($peoples as $item) {
                $exist =DB::connection('logs')->table('enbanca_file_logs')
                    ->where('document', '=', $item->nro_doc)
                    ->where('phone', '=', $item->tel_movil)
                    ->exists();
                if ($exist) {
                    $idException[] = $item->id;
                }
            }
            $clients = DB::connection('mysql')
                ->table('ombu_personas')
                ->orderBy('fecha_alta', 'DESC')
                ->whereNotIn('id', $idException)
                ->limit($limit)
                ->get(['nombre', 'apellido', 'tel_movil', 'nro_doc', 'id']);
            $enBancaFile = [];
            foreach ($clients as $item) {
                $enBanca = new EnBanca();
                $response = $enBanca->validationNumber($item->tel_movil, $item->nro_doc);

                if ($response['status']) {
                    $logId = $this->createLog('info', 'consulta realizada', json_encode($item, JSON_THROW_ON_ERROR));
                    $this->createFileLog([
                        'enbanca_logs_id' => $logId,
                        'id' => $item->id,
                        'message' => 'Consulta realizada en Enbanca para este cliente',
                        'document' => $item->nro_doc,
                        'phone' => $item->tel_movil,
                        'data' => json_encode($response, JSON_THROW_ON_ERROR)
                    ]);
                    $enBancaFile[] = [
                        'documento' => $item->nro_doc ?? '',
                        'teléfono' => '57' . $item->tel_movil ?? '',
                        'match' => $response['data']['match'] ?? '',
                        'score' => $response['data']['contactability'] ?? '',
                        'json' => json_encode($response['data'] ?? [''], JSON_THROW_ON_ERROR) ?? 'consulta fallo'
                    ];

                } else {
                    $this->createLog('error', $response['errorMessage'], json_encode([
                        'errorFile' => $response['errorFile'],
                        'errorLine' => $response['errorLine']
                    ], JSON_THROW_ON_ERROR));
                }
            }

            return [
                'message' => 'success',
                'data' => $enBancaFile,
                'code' => 200,
                'status' => true
            ];
        } catch (Exception $e) {
            $this->createLog('error', $e->getMessage(), json_encode([
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine()
            ], JSON_THROW_ON_ERROR));
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
