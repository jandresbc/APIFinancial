<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use App\Http\Integrations\TruoraServices;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\DB;
use JsonException;

class TruoraController extends Controller
{

    /**
     * @return JsonResponse
     * @throws JsonException
     */
    public function getValidationLink ():JsonResponse
    {
        $truora = new TruoraServices();

        try {
            $result = $truora->getValidationLink();
            $urlBase = 'https://identity.truora.com/?token=';
            $resultToken = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $result['api_key'])[1]))), false, 512, JSON_THROW_ON_ERROR);
            $jsonToken = json_decode($resultToken->additional_data, false, 512, JSON_THROW_ON_ERROR);

            DB::connection('mysql')->table('truora_logs')->insert([
                'message' => 'Ha sido generado correctamente el siguiente link ' . $urlBase . $result['api_key'] . ' con el process_id ' . $jsonToken->process_id,
                'created_at' => date("Y-m-d H:i:s")
            ]);

            return response()->json([
                'message' => 'success',
                'data' => [
                    'link' => $urlBase . $result['api_key'],
                    'process_id' => $jsonToken->process_id
                ],
                'code' => 200,
                'status' => true
            ]);
        } catch (Exception $e) {
            DB::connection('mysql')->table('truora_logs')->insert([
                'typeLog' => 'error',
                'message' => json_encode([
                    'message' => 'Ha ocurrido un error en el sistema al generar el link, por favor intente más tarde',
                    'errorMessage' => $e->getMessage(),
                    'errorLine' => $e->getLine(),
                    'errorFile' => $e->getFile(),
                    'code' => 500,
                    'status' => false
                ], JSON_THROW_ON_ERROR),
                'created_at' => date("Y-m-d H:i:s")
            ]);
            return response()->json([
                'message' => 'Ha ocurrido un error en el sistema al generar el link, por favor intente más tarde',
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
                'code' => 500,
                'status' => false
            ], 500);
        } catch (GuzzleException $e) {
            DB::connection('mysql')->table('truora_logs')->insert([
                'typeLog' => 'error',
                'message' => json_encode([
                    'message' => 'Ha ocurrido un error en el sistema al generar el link, por favor intente más tarde',
                    'errorMessage' => $e->getMessage(),
                    'errorLine' => $e->getLine(),
                    'errorFile' => $e->getFile(),
                    'code' => 500,
                    'status' => false
                ], JSON_THROW_ON_ERROR),
                'created_at' => date("Y-m-d H:i:s")
            ]);
            return response()->json([
                'message' => 'Ha ocurrido un error en el sistema al generar el link, por favor intente más tarde',
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
                'code' => 500,
                'status' => false
            ], 500);
        }

    }

    /**
     * @param $process_id
     * @return JsonResponse
     * @throws JsonException
     */
    public function getStatusValidation ($process_id): JsonResponse
    {
        $truora = new TruoraServices();
        try {
            $result = $truora->getStatusValidation($process_id);
            if ($result['status'] === 'success' && isset($result['validations'])) {
                DB::connection('mysql')->table('truora_status_validation_logs')->insert([
                    'message' => 'Consulta realizada correctamente',
                    'status_process' => $result['status'],
                    'document' => $result['validations'][0]['details']['document_details']['document_number'] ?? null,
                    'process_id' => $process_id,
                    'flow_id' => $result['flow_id'],
                    'date_validation' => date('Y-m-d H:i:s', strtotime($result['creation_date'])),
                    'data' => json_encode($result, JSON_THROW_ON_ERROR),
                    'created_at' => date("Y-m-d H:i:s")
                ]);
            } else {
                DB::connection('mysql')->table('truora_status_validation_logs')->insert([
                    'message' => 'Consulta realizada correctamente',
                    'status_process' => $result['status'],
                    'process_id' => $process_id,
                    'flow_id' => $result['flow_id'],
                    'date_validation' => date('Y-m-d H:i:s', strtotime($result['creation_date'])),
                    'data' => json_encode($result, JSON_THROW_ON_ERROR),
                    'created_at' => date("Y-m-d H:i:s")
                ]);
            }
            DB::connection('mysql')->table('truora_logs')->insert([
                'message' => 'Consulta realizada correctamente con el process_id: ' . $process_id,
                'created_at' => date("Y-m-d H:i:s")
            ]);
            if (isset($result['status'])) {
                return response()->json([
                    'message' => 'success',
                    'data' => [
                        'status' => $result['status']
                    ],
                    'code' => 200,
                    'status' => true
                ]);
            }
            return response()->json([
                'message' => 'link is not used, please use link for activate',
                'code' => 200,
                'status' => true
            ]);
        } catch (Exception $e) {
            DB::connection('mysql')->table('truora_logs')->insert([
                'typeLog' => 'error',
                'message' => json_encode([
                    'message' => 'Ha ocurrido un error en el sistema consultando el proceso, por favor intente más tarde',
                    'errorMessage' => $e->getMessage(),
                    'errorLine' => $e->getLine(),
                    'errorFile' => $e->getFile(),
                    'code' => 500,
                    'status' => false
                ], JSON_THROW_ON_ERROR),
                'created_at' => date("Y-m-d H:i:s")
            ]);
            return response()->json([
                'message' => 'Ha ocurrido un error en el sistema consultando el proceso, por favor intente más tarde',
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
                'code' => 500,
                'status' => false
            ], 500);
        } catch (GuzzleException $e) {
            DB::connection('mysql')->table('truora_logs')->insert([
                'typeLog' => 'error',
                'message' => json_encode([
                    'message' => 'Ha ocurrido un error en el consultando el proceso, por favor intente más tarde',
                    'errorMessage' => $e->getMessage(),
                    'errorLine' => $e->getLine(),
                    'errorFile' => $e->getFile(),
                    'code' => 500,
                    'status' => false
                ], JSON_THROW_ON_ERROR),
                'created_at' => date("Y-m-d H:i:s")
            ]);
            return response()->json([
                'message' => 'Ha ocurrido un error en el sistema consultando el proceso, por favor intente más tarde',
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
                'status' => false
            ], 500);
        }
    }
}
