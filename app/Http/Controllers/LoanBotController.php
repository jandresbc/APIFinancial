<?php

namespace App\Http\Controllers;

use App\Http\Integrations\UltraMsg;
use App\Models\CrLog\WppLog;
use Illuminate\Http\Request;
use App\Http\Services\LoanBotService;
use App\Http\Services\ResultMessageService;
use Exception;
use App\Models\CrLog\MessageWhatsappLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LoanBotController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \JsonException
     */
    public function getMessageWhatsapp(Request $request): JsonResponse
    {
        $data = $request->all();
        try{
//            $whatsAppIntegration = new UltraMsg();
            $whatsAppService = new LoanBotService();
            $varMessageWhatsapp = new MessageWhatsappLog();
            $client = $varMessageWhatsapp->where('from', $data['data']['from'])->orderBy('created_at','DESC')->take(1)->get()->first();
//            $responseMessage = $whatsAppIntegration->requestMessage($data);
            $response = $whatsAppService->getMessage($data, $client->type_client, $client->id_request);
            if (!$response['status']) {
                DB::connection('logs')->table('whatsapp_log')->insert([
                    'message' => $response['errorMessage'],
                    'type' => 'error',
                    'data' => json_encode([
                        'request' => $data,
                        'errorMessage' => $response['errorMessage'],
                        'errorLine' => $response['errorLine'],
                        'errorFile' => $response['errorFile'],
                    ], JSON_THROW_ON_ERROR)
                ]);
                return response()->json($response, 500);
            }
            DB::connection('logs')->table('whatsapp_log')->insert([
                'message' => 'success to received message of the whatsapp',
                'data' => json_encode([
                    'request' => $data,
                    'response' => $response,
                ], JSON_THROW_ON_ERROR)
            ]);
            return response()->json([
                'message' => 'success to received message of the whatsapp',
                'data' => $response,
                'status' => true,
                'code' => 200
            ]);
        }catch(Exception $e){
            DB::connection('logs')->table('whatsapp_log')->insert([
                'message' => $e->getMessage(),
                'type' => 'error',
                'data' => json_encode([
                    'errorMessage' => $e->getMessage(),
                    'errorLine' => $e->getLine(),
                    'errorFile' => $e->getFile(),
                ], JSON_THROW_ON_ERROR)
            ]);
            return response()->json([
                'message' => 'An error has occurred in our system, please try again later',
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }
    public function getTriggerMessage(Request $request):JsonResponse
    {
        try{
            $type_client = $request->type_client ?? "Client";
            $whatsAppService = new LoanBotService();
            if($type_client === 'Client'){
                $data = $whatsAppService->clients($request->requestId, $type_client);
            }else if($type_client === 'Seller'){
                $data = $whatsAppService->seller($request->requestId, $type_client);
            }else{
                return response()->json([
                    'message' => 'Type of user no register in data base',
                    'status' => false,
                    'code' => 404
                ], 404);
            }
            if (!$data['status']) {
                return response()->json($data, 500);
            }
            return response()->json([
                'message' => 'success',
                'status' => true,
                'code' => 200,
                'data' => $data,
            ]);
        }catch(Exception $e){
            return response()->json([
                'message' => 'An error has occurred in our system, please try again later',
                'messageError' => $e->getMessage(),
                'lineError' => $e->getLine(),
                'fileError' => $e->getFile(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }
    public function verifyStatus(Request $request): JsonResponse
    {
        try{
            $response = new ResultMessageService();
            $info = $response->getVerify($request);
            if (!$info['status']) {
                return response()->json($info, $info['code']);
            }
            return response()->json($info);
        }catch(Exception $e){
            return response()->json([
                'message' => 'An error has occurred in our system, please try again later',
                'messageError' => $e->getMessage(),
                'lineError' => $e->getLine(),
                'fileError' => $e->getFile(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }
    public function verifySeller(Request $request): JsonResponse
    {
        try{
            $loan = new LoanBotService();
            $verify = $loan->detectedSeller($request->requestId);
            if (!$verify['status']) {
                return response()->json([
                    'message' => $verify['message'],
                    'status' => $verify['status'],
                    'code' => 200,
                    'data' => $verify,
                ]);
            }
            return response()->json([
                'message' => $verify['message'],
                'status' => $verify['status'],
                'code' => 200,
            ]);
        }catch(Exception $e){
            return response()->json([
                'message' => 'An error has occurred in our system, please try again later',
                'messageError' => $e->getMessage(),
                'lineError' => $e->getLine(),
                'fileError' => $e->getFile(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }

    public function resetProcess(Request $request): JsonResponse
    {
        try{
            $loan = new LoanBotService();
            $response = $loan->resetProcess($request->requestId);
            if (!$response['status']) {
                return response()->json($response, $response['code']);
            }
            return response()->json($response);
        }catch(Exception $e){
            return response()->json([
                'message' => 'An error has occurred in our system, please try again later',
                'messageError' => $e->getMessage(),
                'lineError' => $e->getLine(),
                'fileError' => $e->getFile(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }

    //bot con el fin de iniciar campañas con el cliente
    public function getMessage(Request $request)
    {
        $data = json_decode(json_encode($request->all()),true);
        \Log::debug('Test var fails' . json_encode($data));

        $log = new WppLog();
        $telefono = explode("@",$data["data"]["from"]);
        $telefono[0] = substr($telefono[0], 2);
        $exists = $log::where("from",$telefono[0])->where("response","Si")
        ->whereDate("created_at",now()->toDateString())->exists();

        if(!$exists){
            $log->data = json_encode($data);
            $log->from = $telefono[0];
            $log->to = $data["data"]["to"];
            $log->response = $data["data"]["body"];
            $log->save();
        }else{
            $msg = new UltraMsg();
            $phone = $data["data"]["from"];
            $msn = "Ya tienes una llamada agendada previamente. Espera la llamada de uno de nuestros agentes. Muchas gracias.";
            $resp = $msg->sendMessage($msn,$phone);
            \Log::debug(json_encode($resp));
        }
        return $request;
    }
}
