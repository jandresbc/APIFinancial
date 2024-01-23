<?php

namespace App\Http\Integrations;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use App\Models\CrLog\TangeloLog;
use App\Models\CrCore\Parameters;

class Tangelo
{
    # Url ambiente Mock
    // protected string $urlApi = 'https://stoplight.io/mocks/tangelo/saas:test/63526576/api';
    
    #Url ambiente QA
    #protected string $urlApi = 'https://creditek.qa-tangelolatam.co/risk-model/api';

     #ApiKey QA
    // protected string $apikey = "Y5oWp4m6me1mlr75wvn5y2vzZmnTDIriamJUzjFW";

    #Url ambiente producción.
    protected string $urlApi = 'https://creditek.tangelolatam.co/risk-model/api';

    #ApiKey Producción
    protected string $apikey = "qCEjfTNKEe3IvbGHBbuPc3cvt7nWvTtL7fEt4nyp";

    protected string $product = '6a4ffc8a-4d2d-4ea2-bb4f-8787cc4b2b39';
    protected string $user = '100000001';
    protected string $body = '{"product": "{{product}}","user": "{{user}}","data": {"salary": "{{salary}}","cellphone": "{{phone}}","email": "{{email}}","codigo CIUU": "{{ciuu}}","cedula": "{{document}}","tipo de documento": "{{type_document}}","apellido": "{{lastname}}"}}';

    /**
     * @param $method
     * @param $uri
     * @param array $data
     * @return array
     * @throws GuzzleException
     */
    protected function request ($method = "POST", $uri, $data = '')
    {
        try {
          $url = $this->urlApi . $uri;

          $datos = json_decode($data, true);

          if($method == "POST"){
            // Crear opciones de la petición HTTP
            //Server Mocks: "Content-type: application/json\r\n"
            $opciones = array(
                "http" => array(
                    "header" => ["Content-Type: application/json","x-api-key: ".$this->apikey],
                    "method" => $method,
                    "content" => json_encode($datos), # Agregar el contenido definido antes
                ),
            );
          }else if($method == "GET"){
            // Crear opciones de la petición HTTP
            // Server Mocks: "header" => ["Content-type: application/json","Prefer: code=200, example=success_response"],
            $opciones = array(
              "http" => array(
                  "header" => ["Content-Type: application/json","x-api-key: ".$this->apikey],
                  "method" => $method
              ),
            );
          }
          
          # Preparar petición
          $contexto = stream_context_create($opciones);
          # Hacerla
          $result = file_get_contents($url, false, $contexto);
          if ($result === false) {
              echo "Error haciendo petición";
              exit;
          }

          return json_decode($result, false, 512, JSON_THROW_ON_ERROR);;
        } catch(Exception $e) {
          return [
              'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
              'data' => [
                  'errorMessage' => $e->getMessage(),
                  'errorFile' => $e->getFile(),
                  'errorLine' => $e->getLine()
              ],
              'code' => 500,
              'status' => false
          ];
        }
    }

    public function consumer(Request $request)
    {
        $log = new TangeloLog();

        $exists = $log::where("document", $request->document)->where("state", "active")->where("type", "consumer")->exists();
        
        if (!$exists) {
            $this->body = str_replace("{{product}}", $this->product, $this->body);
            $this->body = str_replace("{{user}}", $this->user, $this->body);
            $this->body = str_replace("{{salary}}", $request->salary, $this->body);
            $this->body = str_replace("{{phone}}", $request->phone, $this->body);
            $this->body = str_replace("{{email}}", $request->email, $this->body);
            $this->body = str_replace("{{ciuu}}", $request->ciuu, $this->body);
            $this->body = str_replace("{{document}}", $request->document, $this->body);
            $this->body = str_replace("{{type_document}}", $request->type_document, $this->body);
            $this->body = str_replace("{{lastname}}", $request->lastname, $this->body);

            $result = $this->request("POST","/v1/consumer",$this->body);
            
            $this->log($result, $request, "consumer");

            $return = [
                'status' => true,
                'code' => 200,
                'message' => 'success',
                'data' => [
                    "status" => $result->steps->datacredito_historialcredito_data->status,
                    "process" => $result->process,
                    "created_at" => $result->created_at
                ]
            ];
        } else {
            $result = $log::where("state", "active")->where("document", $request->document)->where("type", "consumer")->first();

            $mareigua = json_decode($result->steps);

            $return = [
                'status' => true,
                'code' => 200,
                'message' => 'success',
                'data' => [
                    "status" => $mareigua->datacredito_historialcredito_data->status,
                    "process" => $result->process,
                    "created_at" => $result->created_at
                ]
            ];
        }
        return $return;
    }

    public function getStatus(Request $request)
    {
        try {
            $log = new TangeloLog();
            $return = [];

            $exists = $log::where("document", $request->document)->where("state", "active")->where("type", "consumer")->latest()->exists();
            
            //Si existe consulta a tangelo.
            if($exists){
                $now = new \DateTime("now");
                $dueDate = new \DateTime("now");

                $config = Parameters::where("grupo_id",23)->where("nombre","days_duedate")->first();
                
                $dueDate->add(new \DateInterval("P".$config->valor."D"));
                
                $diff = $now->diff($dueDate);

                $process = $log::where("state", "active")->where("type", "consumer")->where("document", $request->document)->latest()->first();
                
                if($diff->days > 0){
                    $result = $log::where("state", "active")->where("type", "status")->where("document", $request->document)->latest()->first();

                    if($result == null){
                        $uri = "/v1/consumer?process=".$process->process;

                        $result = $this->request("GET",$uri);
                    }else{
                        $result = json_decode($result->data);
                        // echo "usa datos de bD";
                    }
                }else{
                    // echo "consulta nuevamente";
                    $uri = "/v1/consumer?process=".$process->process;

                    $result = $this->request("GET",$uri);
                }

                $steps = json_decode($process->steps, true);
                
                //Validar pasos completos.
                $stepsvalidate = $this->steps($steps,$result);
                //   $stepsvalidate = false;

                if ($stepsvalidate === true) {
                    if(isset($result->data->datacredito_historialcredito_score)){
                        $score = ($result->data->datacredito_historialcredito_score->datacredito_score);//* 100
                        $cedula = $result->data->data_request->cedula;
                        $decil = $result->data->datacredito_historialcredito_score->datacredito_decil;
                        $approved = $result->data->datacredito_historialcredito_score->datacredito_approved;

                        if(isset($result->result)){
                            $resultado = json_decode(json_encode($result->result), true);
                            
                            if(count($resultado) > 0 && $resultado["pre-offers"]["request_status"] == "Rejected"){
                                $approved = false;
                            }
                        }
                        
                        $return = [
                            "message"=>"Process successfull",
                            "process" => $result->process->process,
                            "status" => "SUCCESS",
                            "score" => $score,
                            "approved" => $approved,
                            "identification" => $cedula,
                            "decil" => $decil
                        ];

                        $process->state = "inactive";
                        $process->save();
                    }else{
                        $return = [
                            "message"=>"Process successfull",
                            "process" => $result->process->process,
                            "status" => "SUCCESS",
                            "score" => 0,
                            "approved" => false,
                            "identification" => $request->document,
                            "decil" => 0
                        ];
                    }
                } else if ($stepsvalidate === false) {
                    $return = [
                        "message"=>"Process successfull",
                        "process" => $result->process->process,
                        "status" => "PENDING",
                        "score" => 0,
                        "approved" => false,
                        "identification" => $request->document,
                        "decil" => 0
                    ];
                }
                $this->log($result, $request, "status");
                return [
                    'status' => true,
                    'code' => 200,
                    'message' => 'Done',
                    'data' => $return
                ];

            }else{ //Si no existe y el sistema lo ha consultado(Estado inactive). Usa esa información
            //   $datos = $log::where("document", $request->document)->where("state", "active")->where("type", "status")->first();
            //   $data = json_decode($datos->data);

            //   $score = ($datos->score);//* 100
            //   $cedula = $data->data->data_request->cedula;
            //   $decil = $data->data->datacredito_historialcredito_score->datacredito_decil;
            //   $approved = $datos->approved == 1 ? true : false;

            //   return [
            //     'status' => true,
            //     'code' => 200,
            //     'message' => 'Done',
            //     'data' => [
            //         "message"=>"Process successfull",
            //         "process" => $datos->process,
            //         "status" => "SUCCESS",
            //         "score" => $score,
            //         "approved" => $approved,
            //         "identification" => $cedula,
            //         "decil" => $decil
            //     ]
            //   ];

                $return = [
                        'status' => true,
                        'code' => 200,
                        'message' => 'Done',
                        'data' =>[
                            "message"=>"Process successfull",
                            "process" => $result->process->process,
                            "status" => "SUCCESS",
                            "score" => 0,
                            "approved" => false,
                            "identification" => $request->document,
                            "decil" => 0
                        ]
                ];
            }
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

    private function log($response, $request, $type)
    {
        $log = new TangeloLog();

        if ($type == 'consumer') {
            $exists = $log::where("document", $request->document)->where("state","active")->where("type", "consumer")->exists();
            
            if (!$exists) {
                $log->document = $request->document;
                $log->state = "active";
                $log->type = $type;
                $log->process = $response->process;
                $log->user = $response->user;
                $log->product = $response->product;
                $log->consult_at = $response->created_at;
                $log->steps = json_encode($response->steps);
                $log->request = json_encode($request->all());
                $log->data = json_encode($response);
                $log->save();
            }
        } else if ($type == "status") {
          $exists = $log::where("document", $request->document)->where("state","active")->where("type","status")->exists();
            
        //   if (!$exists) {
              $logconsumer = $log::where("document", $request->document)->where("state","active")->where("type","consumer")->first();
                $approved = $response->data->datacredito_historialcredito_score->datacredito_approved;
                if(isset($response->result)){
                    $resultado = json_decode(json_encode($response->result), true);
                    
                    if(count($resultado) > 0 && $resultado["pre-offers"]["request_status"] == "Rejected"){
                        $approved = false;
                    }
                }

              $log->document = $request->document;
              $log->state = "active";
              $log->type = $type;
              $log->process = $response->process->process;
              $log->score = $response->data->datacredito_historialcredito_score->datacredito_score;
              $log->approved = $approved == true ? 1 : 0;
              $log->user = $response->process->user;
              $log->product = $response->process->product;
              $log->consult_at = $response->process->created_at;
              $log->steps = json_encode($response->process->steps);
              $log->request = json_encode($request->all());
              $log->data = json_encode($response);
              $log->save();

              //Actualiza el estado del log de la consulta del cliente.
            //   $logconsumer->state = "inactive";
            //   $logconsumer->save();
        //   }
        }

        return true;
    }

    private function steps($steps, $response)
    {
        $stepsvalidate = false;
        $results = null;

        if(isset($response->result)){
            $results = $response->result;
        }

        $response = $response->process->steps;

        foreach ($steps as $i => $step) {
            if ($response->datacredito_historialcredito_trigger->status == "SUCCESS" && $response->datacredito_historialcredito_data->status == "SUCCESS" && $response->datacredito_historialcredito_score->status == "SUCCESS" && $response->mareigua_socialsecurity_trigger->status == "SUCCESS" && $response->mareigua_socialsecurity_data->status == "SUCCESS") {
                $stepsvalidate = true;
            } else {
                $stepsvalidate = false;
            }
        }
        
        if($results != null){
            $stepsvalidate = true;
        }
        
        return $stepsvalidate;
    }
}
