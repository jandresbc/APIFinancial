<?php

namespace  App\Http\Services;

use App\Http\Integrations\UltraMsg;
use App\Models\CrCore\config_employees;
use App\Models\CrCore\Ombu_cc_prestamos;
use App\Models\CrCore\Ombu_solicitudes;
use App\Models\CrCore\OmbuCcCuotas;
use App\Models\CrCore\ProdProductos;
use App\Models\CrLog\MessageWhatsappLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;
use UltraMsg\WhatsAppApi;
use Exception;

class LoanBotService{
    public const TOKEN = '';
    public const INSTANCE_ID = '';
    public const MESSAGE = '';
    public const ID = '';
    /**
     * @var array|string|string[]
     */
    private string|array $MESSAGE;

    private $ID;

    public function sendMessage($phone, $message, $filename, $phatfilename, $imagename, $phatimagename, $id): array
    {
        $messagePredetermined = new ParameterService();
        $TOKEN = $messagePredetermined->get(20, 'token_ultramsg'); // Ultramsg.com token
        $INSTANCE_ID = $messagePredetermined->get(20, 'instanceID_ultramsg'); // Ultramsg.com instance id
        $client = new WhatsAppApi($TOKEN, $INSTANCE_ID);
        try {
            if($id && $id !== "") {
                $link = "https://checkout.wompi.co/l/".$id;
                $text = " y evita ser bloqueado";
            }else{
                $link = "";
                $text = "";
            }

            $resultMessage = $client->sendChatMessage($phone, $message."  ".$link.$text);

            if($filename && $phatfilename){
                $resultFile = $client->sendDocumentMessage($phone, $filename, $phatfilename);
            }else{
                $resultFile = '';
            }
            if($imagename && $phatimagename){
                $resultImage = $client->sendImageMessage($phone, $imagename, $phatimagename);
            }else{
                $resultImage = '';
            }

            $result = [
                'responseMessage' => $resultMessage,
                'responseFile' => $resultFile,
                'responseImage' => $resultImage
            ];
            return [
                'message' => 'success',
                'data' => [$result],
                'link_payment' => $link,
                'text' => $message,
                'responseMessage' => $message.$link." y evita ser bloqueado",
                'code' => 200,
                'status' => true
            ];
        } catch (Exception $e) {
            return [
                'message' => 'Warn',
                'errorMessage' => $e->getMessage(),
                'errorCode' => $e->getCode(),
                'code' => 500,
                'status' => false
            ];
        }
    }
    private function anullCredit(): void
    {
        $request = new Ombu_solicitudes();
        $responseRequest = $request->where('id', $this->ID)->get()->first();
        $request->where('id', $this->ID)->update(["estado_solicitud" => "ANUL"]);
        $loan = new Ombu_cc_prestamos();
        $responseLoan = $loan->where('solicitud_id', $responseRequest['id'])->get();
        $id_Loan = $loan->where('solicitud_id', $responseRequest['id'])->get()->first();
        foreach($responseLoan as $responseL){
            $responseL->estado = "ANUL";
            $responseL->save();
        }
        $dues = new OmbuCcCuotas();
        $responseDues = $dues->where('prestamo_id', $id_Loan['id'])->get();
        foreach($responseDues as $responseD){
            $responseD->estado = "ANUL";
            $responseD->save();
        }
    }

    public function seller($loan_id, $typeClient): array
    {
        try{
            $timeOut = 0;
            do {
                $responseRequest = Ombu_solicitudes::where('id', $loan_id)->first();
                $data = json_decode($responseRequest['data'], false, 512, JSON_THROW_ON_ERROR);
                $requestEmployees = config_employees::where('id', $data->vendedor_id)->first();
                $dataEmployees = json_decode($requestEmployees['data'], false, 512, JSON_THROW_ON_ERROR);
                //get costumer data
                $responseLoan = Ombu_cc_prestamos::where('solicitud_id', $responseRequest['id'])->first();
                $responseDues = null;
                if (isset($responseLoan['id'])) {
                    $responseDues = OmbuCcCuotas::where('prestamo_id', $responseLoan['id'])->first();
                }
                $responseProduct = ProdProductos::where('id', $data->producto_id)->first();
                sleep(1);
                $timeOut++;
                if ($timeOut === 10) {
                    return [
                        'message' => 'An error has occurred in our system, please try again later',
                        'errorMessage' => 'Time out execution in seller',
                        'code' => 500,
                        'status' => false
                    ];
                }
            } while(!isset($responseRequest) && !isset($responseLoan) && !isset($responseDues));
            //get message
            $messagePredetermined = new ParameterService();
            $date = Carbon::parse($responseLoan['fechahora'])->locale('es');
            $message = $messagePredetermined->get(18, 'Mensaje_bienvenida_seller', $typeClient);
            $message = str_replace(array('{nombre_vendedor}', '{fecha_actual}', '{nombre_cliente}', '{nombre_producto}', '{cuota_inicial}', '{valor_financiar}', '{monto_cuota}', '{plazo_credito}'), array($requestEmployees->nombre . ' ' . $requestEmployees->apellido, $date->isoFormat('d/m/Y, h:mm:ss a'), $data->nombre . ' ' . $data->apellido, $responseProduct['nombre'], "$" . number_format($data->int_enganche, 0, ',', '.'), "$" . number_format($data->int_monto_financiar, 0, ',', '.'), '$' . number_format($responseDues['monto_cuota'], 0, ',', '.'), $responseLoan['periodo']), $message);
            $this->MESSAGE = str_replace('{cuotas_totales}', $responseLoan['periodo'] * 2, $message);
            $this->ID = $loan_id;
            $receivedMessage = new UltraMsg();
            $request = $this->getMessage($receivedMessage->TriggerMessage($dataEmployees->telefono), 'Seller', $loan_id);
            return [
                'message' => 'success',
                'data' => $request,
                'code' => 200,
                'status' => true
            ];
        }catch(Exception $e){
            return [
                'message' => 'Dato no encontrado',
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
                'code' => 500,
                'status' => false
            ];
        }
    }

    public function clients($loan_id, $typeClient): array
    {
        try{
            $timeOut = 0;
            do {

                $responseRequest = DB::table('ombu_solicitudes')
                    ->where('estado_solicitud', '!=', 'ANUL')
                    ->Where('estado_solicitud', '!=', 'RECH')
                    ->where('id', $loan_id)
                    ->first();
                if (isset($responseRequest)) {
                    $responseLoan = Ombu_cc_prestamos::where('solicitud_id', '=', $responseRequest->id)->first();
                }
                $responseDues = null;
                if (isset($responseLoan['id'])) {
                    $responseDues = OmbuCcCuotas::where('prestamo_id', '=', $responseLoan['id'])->first();
                }
                sleep(1);
                $timeOut++;
                if ($timeOut === 10) {
                    return [
                        'message' => 'An error has occurred in our system, please try again later',
                        'errorMessage' => 'Time out execution in clients',
                        'code' => 500,
                        'status' => false
                    ];
                }
            } while(!isset($responseRequest) && !isset($responseLoan) && !isset($responseDues));
            $data = json_decode($responseRequest->data, false, 512, JSON_THROW_ON_ERROR);
            $responseProduct = ProdProductos::where('id', $data->producto_id)->first();
            $messagePredetermined = new ParameterService();
            $date = Carbon::parse($responseLoan['fechahora'])->locale('es');
            $message = $messagePredetermined->get(18,'Mensaje_de_bienvenida', $typeClient);
            $message = str_replace(array('{fecha_actual}', '{nombre_cliente}', '{nombre_producto}', '{cuota_inicial}', '{valor_financiar}', '{monto_cuota}', '{plazo_credito}'), array($date->isoFormat('d/m/Y, h:mm:ss a'), $data->nombre . ' ' . $data->apellido, $responseProduct['nombre'], "$" . number_format($data->int_enganche, 0, ',', '.'), "$" . number_format($data->int_monto_financiar, 0, ',', '.'), '$' . number_format($responseDues['monto_cuota'], 0, ',', '.'), $responseLoan['periodo']), $message);
            $this->MESSAGE = str_replace('{cuotas_totales}', $responseLoan['periodo'] * 2, $message);
            $this->ID = $loan_id;
            $receivedMessage = new UltraMsg();
            $request = $this->getMessage($receivedMessage->TriggerMessage($data->tel_movil), 'Client', $loan_id);
            return [
                'message' => 'success',
                'data' => $request,
                'code' => 200,
                'status' => true
            ];
        }catch(Exception $e){
            return [
                'message' => 'Dato no encontrado',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ];
        }
    }

    public function getMessage($request, $typePerson, $loan_id): array
    {
        try{
            $validatorEx = true;
            $messagePredetermined = new ParameterService();
            $varMessageWhatsapp = new MessageWhatsappLog();
            $receivedMessage = new UltraMsg();
            $resultMessageService = new ResultMessageService();
            $jsonId = '{
                "requestId": '.$loan_id.'
            }';
            $resultMessage = $resultMessageService->getVerify(json_decode($jsonId, false, 512, JSON_THROW_ON_ERROR));
            $response = '';
            $phone = trim($request['data']['from'], '@c.us');
            $client = $varMessageWhatsapp->where('from', $request['data']['from'])->orderBy('created_at','DESC')->take(1)->get();
            if(count($client->toArray()) === 0){
                $varMessageWhatsapp->response_client = 'welcome';
                $varMessageWhatsapp->validator = 1;
                $varMessageWhatsapp->state = "active";
                $message = $this->MESSAGE;
                $response = $receivedMessage->sendMessage($message, $phone);
            }else{
                foreach($client as $data){
                    if($typePerson === "Client" && $data->validator < 3 && $data->state === 'close'){
                        $varMessageWhatsapp->response_client = 'welcome';
                        $varMessageWhatsapp->validator = $data->validator + 1;
                        $varMessageWhatsapp->state = "active";
                        $message = $this->MESSAGE;
                        $response = $receivedMessage->sendMessage($message, $phone);
                    } else if($typePerson === "Seller" && ($data->response_client === "success" || $data->response_client === "failed") && $data->id_request !== $loan_id){
                        if($varMessageWhatsapp->where('type_client','Seller')->where('id_request', $loan_id)->where("response_client","success")->exists() === false &&
                            $varMessageWhatsapp->where('type_client','Seller')->where('id_request', $loan_id)->where("response_client","failed")->exists() === false){
                            $varMessageWhatsapp->response_client = 'welcome';
                            $varMessageWhatsapp->validator = 1;
                            $varMessageWhatsapp->state = "active";
                            $message = $this->MESSAGE;
                        }else{
                            $varMessageWhatsapp->response_client = $data->response_client;
                            $varMessageWhatsapp->validator = $data->validator;
                            $varMessageWhatsapp->state = "active";
                            $message = $messagePredetermined->get(18, 'Solicitud_finalizada', $typePerson);
                        }
                        $response = $receivedMessage->sendMessage($message, $phone);
                    } else if($data->validator <= 3 && $data->state === 'active' && $request['data']['body'] !== 'init'){
                        $this->ID = $loan_id;
                        if(($typePerson === "Client" && $data->response_client === "welcome") || $data->response_client === "validate_fail"){
                            $messageService = new UserMessageService();
                            $varverif = false;
                            foreach($messageService::APPROVED as $dataMessage){
                                if($dataMessage === strtolower($request['data']['body'])){
                                    $varMessageWhatsapp->response_client = 'si';
                                    $varMessageWhatsapp->validator = $data->validator;
                                    $varMessageWhatsapp->state = "active";
                                    $message = $messagePredetermined->get(18, 'Mensaje_de_aceptacion', $typePerson);
                                    $response = $receivedMessage->sendMessage($message, $phone);
                                    $varverif = true;
                                }
                            }
                            foreach($messageService::DENIED as $dataMessage){
                                if($dataMessage === strtolower($request['data']['body'])){
                                    $varMessageWhatsapp->response_client = 'no';
                                    $varMessageWhatsapp->validator = $data->validator;
                                    $varMessageWhatsapp->state = "active";
                                    $message = $messagePredetermined->get(18, 'Mensaje_de_negacion', $typePerson);
                                    $response = $receivedMessage->sendMessage($message, $phone);
                                    $varverif = true;
                                }
                            }if($varverif === false){
                                $varMessageWhatsapp->response_client = 'validate_fail';
                                $varMessageWhatsapp->validator = $data->validator;
                                $varMessageWhatsapp->state = "active";
                                $message = $messagePredetermined->get(18, 'Opcion_no_valida', $typePerson);
                                $response = $receivedMessage->sendMessage($message, $phone);
                            }
                        } else if($data->response_client === 'no' && $typePerson === "Client"){
                            switch(strtolower($request['data']['body'])){
                                case "1":
                                case "si, anular crédito":
                                case "si, anular credito":
                                case "si, anular":
                                case "si":
                                    $varMessageWhatsapp->response_client = 'failed';
                                    $varMessageWhatsapp->validator = $data->validator;
                                    $varMessageWhatsapp->state = "active";
                                    $message = $messagePredetermined->get(18, 'Nego_credito', $typePerson);
                                    $response = $receivedMessage->sendMessage($message, $phone);
                                    $this->anullCredit();
                                    break;
                                case "2";
                                case "no":
                                    $varMessageWhatsapp->response_client = 'validate_fail';
                                    $varMessageWhatsapp->state = "active";
                                    $varMessageWhatsapp->validator = $data->validator + 1;
                                    $message = $messagePredetermined->get(18, 'Opcion_no_valida', $typePerson);
                                    $response = $receivedMessage->sendMessage($message, $phone);
                                    break;
                            }
                        } else if($data->response_client === 'no' && $typePerson === "Seller"){
                            switch(strtolower($request['data']['body'])){
                                case "1":
                                case "si, anular crédito":
                                case "si, anular credito":
                                case "si, anular":
                                case "si":
                                    $varMessageWhatsapp->response_client = 'failed';
                                    $varMessageWhatsapp->validator = $data->validator;
                                    $varMessageWhatsapp->state = "active";
                                    $message = $messagePredetermined->get(18, 'Credito_anulado_seller', $typePerson);
                                    $response = $receivedMessage->sendMessage($message, $phone);
                                    $this->anullCredit();
                                    break;
                                case "2";
                                case "no":
                                    $varMessageWhatsapp->response_client = 'welcome';
                                    $varMessageWhatsapp->state = "active";
                                    $varMessageWhatsapp->validator = $data->validator + 1;
                                    $message = $messagePredetermined->get(18, 'Aceptar_credit', $typePerson);
                                    $response = $receivedMessage->sendMessage($message, $phone);
                                    break;
                            }
                        } else if($typePerson === "Client" && ($data->response_client === 'si'|| $data->response_client === 'document_failed')){
                            $requestSol = new Ombu_solicitudes();
                            $responseRequest = $requestSol->where('id', $this->ID)->get()->first();
                            $dataRequest = json_decode($responseRequest['data'], false, 512, JSON_THROW_ON_ERROR);
                            if(strlen($request['data']['body']) >= 6 && is_numeric($request['data']['body'])){
                                if($dataRequest->nro_doc === $request['data']['body']){
                                    $varMessageWhatsapp->response_client = 'success';
                                    $varMessageWhatsapp->validator = $data->validator;
                                    $varMessageWhatsapp->state = "active";
                                    $varMessageWhatsapp->id_identification = $request['data']['body'];
                                    $message = $messagePredetermined->get(18, 'Mensaje_de_confirmacion', $typePerson);
                                }else{
                                    $varMessageWhatsapp->response_client = 'document_failed';
                                    $varMessageWhatsapp->validator = $data->validator;
                                    $varMessageWhatsapp->state = "active";
                                    $message = $messagePredetermined->get(18, 'Unrecognized_document', $typePerson);
                                }
                            }else{
                                $varMessageWhatsapp->response_client = 'document_failed';
                                $varMessageWhatsapp->validator = $data->validator;
                                $varMessageWhatsapp->state = "active";
                                $message = $messagePredetermined->get(18, 'Cedula_no_valida', $typePerson);
                            }
                            $response = $receivedMessage->sendMessage($message, $phone);
                        }
                        //Function seller
                        else if($typePerson === "Seller" && ($data->response_client === "welcome" || $data->response_client === "validate_fail")){
                            $messageService = new UserMessageService();
                            $varverif = false;
                            foreach($messageService::APPROVED as $dataMessage){
                                if($dataMessage === strtolower($request['data']['body'])){
                                    $varMessageWhatsapp->response_client = 'success';
                                    $varMessageWhatsapp->validator = $data->validator;
                                    $varMessageWhatsapp->state = "active";
                                    $message = $messagePredetermined->get(18, 'Mensaje_finalizacion', $typePerson);
                                    $response = $receivedMessage->sendMessage($message, $phone);
                                    $varverif = true;
                                }
                            }
                            foreach($messageService::DENIED as $dataMessage){
                                if($dataMessage === strtolower($request['data']['body'])){
                                    $varMessageWhatsapp->response_client = 'no';
                                    $varMessageWhatsapp->validator = $data->validator;
                                    $varMessageWhatsapp->state = "active";
                                    $message = $messagePredetermined->get(18, 'Confirmacion_negar_credito_seller', $typePerson);
                                    $response = $receivedMessage->sendMessage($message, $phone);
                                    $varverif = true;
                                }
                            }if($varverif === false){
                                $varMessageWhatsapp->response_client = 'validate_fail';
                                $varMessageWhatsapp->validator = $data->validator;
                                $varMessageWhatsapp->state = "active";
                                $message = $messagePredetermined->get(18, 'Opcion_no_valida', $typePerson);
                                $response = $receivedMessage->sendMessage($message, $phone);
                            }
                        } else{
                            $varMessageWhatsapp->response_client = 'success';
                            $varMessageWhatsapp->validator = $data->validator;
                            $varMessageWhatsapp->state = "active";
                            $message = $messagePredetermined->get(18, 'Mensaje_de_completado', '');
                            $response = $receivedMessage->sendMessage($message, $phone);
                        }
                    }
                    else if($data->response_client === 'failed'){
                        $varMessageWhatsapp->response_client = 'failed';
                        $varMessageWhatsapp->validator = $data->validator;
                        $varMessageWhatsapp->state = "close";
                        $message = $messagePredetermined->get(18, 'Mensaje_de_negacion_whatsapp', $typePerson);
                        $response = $receivedMessage->sendMessage($message, $phone);
                    }
                    else if($resultMessage['message'] === "success" || $resultMessage['message'] === "failed"){
                        $varMessageWhatsapp->response_client = $data->response_client;
                        $varMessageWhatsapp->validator = $data->validator;
                        $varMessageWhatsapp->state = "active";
                        $message = $messagePredetermined->get(18, 'Solicitud_finalizada', $typePerson);
                        $response = $receivedMessage->sendMessage($message, $phone);
                        $validatorEx = false;
                    }
//                    else {
//                        $varMessageWhatsapp->response_client = $data->response_client;
//                        $varMessageWhatsapp->validator = $data->validator;
//                        $varMessageWhatsapp->state = "active";
//                        $message = $messagePredetermined->get(18, 'Proceso_activo', $typePerson);
//                        $response = $receivedMessage->sendMessage($message, $phone);
//                        $validatorEx = false;
//                    }
                }
            }
            $varMessageWhatsapp->whatsapp_id = $request['data']['id'];
            $varMessageWhatsapp->phone_client = $phone;
            $varMessageWhatsapp->from = $request['data']['from'];
            $varMessageWhatsapp->id_request = $loan_id;
            $varMessageWhatsapp->to = $request['data']['to'];
            $varMessageWhatsapp->ack = $request['data']['ack'];
            $varMessageWhatsapp->type = $request['data']['type'];
            $varMessageWhatsapp->body = $request['data']['body'];
            $varMessageWhatsapp->fromMe = $request['data']['fromMe'];
            $varMessageWhatsapp->type_client = $typePerson;
            $varMessageWhatsapp->time = $request['data']['time'];
            $varMessageWhatsapp->created_at = date("Y-m-d H:i:s");
            $varMessageWhatsapp->updated_at = date("Y-m-d H:i:s");
            $hashes = $request['data']['id']."$%$".$phone."$%$".$request['data']['from']."$%$".$request['data']['to']."$%$".$request['data']['ack']."$%$".$request['data']['body']."$%$".$request['data']['fromMe']."$%$".$request['data']['time']."$%$".$varMessageWhatsapp->created_at."$%$".$varMessageWhatsapp->response_client."$%$".$varMessageWhatsapp->id_identification."$%$";
            $varMessageWhatsapp->hash = hash('sha256', $hashes);
            if($validatorEx === true){
                $varMessageWhatsapp->save();
            }
            return [
                'status' => true,
                'message' => 'success',
                'response' => $response
            ];
        }catch(Exception $e){
            return [
                'message' => 'Warn',
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
                'code' => 500,
                'status' => false
            ];
        }
    }

    /**
     * Function for detected if the seller has credit active
     * @throws \JsonException
     */
    public function detectedSeller($loan_id): array
    {
        $responseRequest = Ombu_solicitudes::where('id', $loan_id)->first();
        $data = json_decode($responseRequest['data'], false, 512, JSON_THROW_ON_ERROR);
        $requestEmployees = config_employees::where('id', $data->vendedor_id)->first();
        $dataEmployees = json_decode($requestEmployees['data'], false, 512, JSON_THROW_ON_ERROR);
        $client = MessageWhatsappLog::where('phone_client', '=', '57'.$dataEmployees->telefono)->where('type_client', '=', 'Seller')->orderBy('created_at','DESC')->first();
        try {
            if (!isset($client)) {
                return [
                    'message'=>'success',
                    'status' => true
                ];
            }
            if(!$client->response_client || $client->response_client === 'success' || $client->response_client === 'failed'){
                return [
                    'message'=>'success',
                    'status' => true
                ];
            }
            return [
                'message' => 'failed',
                'question' => 'The seller is already in a request',
                'status' => false
            ];
        } catch (Exception $e) {
            return [
                'message' => 'An error has occurred in our system, please try again later',
                'messageError' => $e->getMessage(),
                'lineError' => $e->getLine(),
                'fileError' => $e->getFile(),
                'code' => 500,
                'status' => false
            ];
        }
    }

    /**
     * @param $loan_id
     * @return array
     */
    public function resetProcess($loan_id): array
    {
        $res = DB::connection('logs')->table('message_whatsapp_log')
            ->where('id_request', '=', $loan_id)
            ->delete();
        try {
            return [
                'message' => 'logs are deleted, successfully',
                'status' => true,
                'code' => 200,
                'data' => $res
            ];
        } catch (Exception $e) {
            return [
                'message' => 'An error has occurred in our system, please try again later',
                'messageError' => $e->getMessage(),
                'lineError' => $e->getLine(),
                'fileError' => $e->getFile(),
                'code' => 500,
                'status' => false
            ];
        }
    }
}
