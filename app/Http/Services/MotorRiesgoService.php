<?php

namespace App\Http\Services;
use App\Http\Integrations\Tangelo;
use App\Http\Services\TangeloService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use App\Http\Integrations\DataCredito;
use Illuminate\Support\Facades\DB;

class MotorRiesgoService extends Service
{
    // Esta función tendrá toda la validación del motor de decisión integrada.
    // Se unirán todas las integraciones para la toma de decisiones.
    /**
     * @param $data
     * @return array
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function createRiskDecision($data): array
    {
        $tangeloStatus = DB::connection('mysql')->table('config_parametros')
            ->where('grupo_id', '=', 23)
            ->where('nombre', '=', 'tangelo_risk_service')
            ->where('estado', '=', 'HAB')
            ->first();
        if (isset($tangeloStatus) && $tangeloStatus->valor === 'active') {
            $request = new Request($data);
            return (new TangeloService())->consumer($request);
        }

        $dc = new DataCredito();

        $response = $dc->getDataCredito($data);

        if (!$response['status']) {
            $dataLog = $response['data'];
            $dataLog['document'] = $data['document'];
            $dataLog['status'] = 'REJECTED';
            $this->createLog('Error in validation client', 'error', $dataLog);
            return $this->Error($response['message'], $response['data'], $response['code']);
        }
        $dataLog['response'] = $response['data'];
        $dataLog['document'] = $data['document'];
        if ($response['data']->data->item->status === 'APROBADO') {
            $dataLog['status'] = 'APPROVED';
            $this->createLog('Validation client success', 'info', $dataLog);
            return $this->RiskOk($response['data']->data->item->status, $data['document']);
        }

        $dataLog['status'] = 'REJECTED';
        $this->createLog('Validation client success', 'info', $dataLog);

        return $this->RiskRejected($response['data']->data->item->status, $data['document']);
    }

    //Obtiene el estado del proceso de validación en el motor de riesgo.
    public function getRisksDecision(Request $request)
    {
        $tangeloStatus = DB::connection('mysql')->table('config_parametros')
        ->where('grupo_id', '=', 23)
        ->where('nombre', '=', 'tangelo_risk_service')
        ->where('estado', '=', 'HAB')
        ->first();

        if (isset($tangeloStatus) && $tangeloStatus->valor === 'active') {
            // $request = new Request($process);
            // return (new TangeloService())->getStatus($request);

            $tangelo = new TangeloService();
            $status = $tangelo->getStatus($request);
            $now = new \DateTime("now");
            // dd($status);

            //Validación motor de decisión integrada
            if(isset($status["data"]) && count($status["data"]) > 0){

                //Decision de aprobación.
                $score = $status["data"]["score"];
                $approved = $status["data"]["approved"];
                $state = $status["data"]["status"];
                // $approved = true;
                
                if($state == "SUCCESS"){
                    if($approved === true){
                        return [
                            "status" => true,
                            "code" => 200,
                            "message" => "success",
                            "data" => [
                                //"date_consult" => $now->format("Y-m-d H:m:s"),
                                //"status" => $status["data"]["status"],
                                "message" => "approved",
                                "validation" => true,
                                "document" => $status["data"]["identification"],
                                "score" => $score
                            ]
                        ];
                    }else if($approved === false){
                        return [
                            "status" => true,
                            "code" => 200,
                            "message" => "success",
                            "data" => [
                                // "date_consult" => $now->format("Y-m-d H:m:s"),
                                // "status" => $status["data"]["status"],
                                "message" => "not approved",
                                "validation" => false,
                                "document" => $status["data"]["identification"],
                                "score" => $score
                            ]
                        ];
                    }
                }else if($state == "PENDING"){
                    return [
                        "status" => true,
                        "code" => 200,
                        "message" => "success",
                        "data" => [
                            // "date_consult" => $now->format("Y-m-d H:m:s"),
                            // "status" => $status["data"]["status"],
                            "message" => "pending",
                            "validation" => false,
                            "document" => $status["data"]["identification"],
                            "score" => 0
                        ]
                    ];
                }
            }else{
                return [
                    "status" => true,
                    "code" => 200,
                    "message" => "success",
                    "data" => [
                        // "date_consult" => $now->format("Y-m-d H:m:s"),
                        // "status" => null,
                        "message" => "not approved",
                        "validation" => false,
                        "document" => $request->document,
                        "score" => 0
                    ]
                ];
            }
        }
        
        
        
    }

    /**
     * @param $message
     * @param string $type
     * @param array $data
     * @return void
     * @throws \JsonException
     *
     * function create log for validation risk
     */
    public function createLog($message, string $type = 'info', array $data = []):void
    {
        $log = DB::connection('logs')->table('risk_validation');

        $log->insert([
            'document' => $data['document'],
            'message' => $message,
            'type' => $type,
            'status' => $data['status'],
            'data' => json_encode($data, JSON_THROW_ON_ERROR)
        ]);
    }

}
