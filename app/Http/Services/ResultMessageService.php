<?php
namespace  App\Http\Services;

use App\Models\CrLog\MessageWhatsappLog;
use Exception;
use Illuminate\Support\Facades\DB;

class ResultMessageService{
    public function getVerify($request): array
    {
        try{
            $successClient = DB::connection('logs')->table('message_whatsapp_log')
                ->where('id_request', $request->requestId)
                ->where('type_client','Client')
                ->where("response_client","success")
                ->exists();
            $successSeller = DB::connection('logs')->table('message_whatsapp_log')
                ->where('id_request', $request->requestId)
                ->where('type_client','Seller')
                ->where("response_client","success")
                ->exists();
            if($successClient && $successSeller){
                return [
                    'message' => 'success',
                    'data' => [
                        "validation" => true,
                        "message" => "success"
                    ],
                    'code' => 200,
                    'status' => true
                ];
            }
            $failedClient = DB::connection('logs')->table('message_whatsapp_log')
                ->where('id_request', $request->requestId)
                ->where('type_client','Client')
                ->where("response_client","failed")
                ->exists();
            $failedSeller = DB::connection('logs')->table('message_whatsapp_log')
                ->where('id_request', $request->requestId)
                ->where('type_client','Seller')
                ->where("response_client","failed")
                ->exists();
            if($failedClient || $failedSeller){
                return [
                    'message' => 'failed',
                    'data' => [
                        'validation' => true,
                        'message' => "El crédito ha sido anulado, por favor radique una nueva solicitud."
                    ],
                    'code' => 200,
                    'status' => true
                ];
            }

            return [
                'message' => 'pending',
                'data' => [
                    'validation' => false,
                    'message' => "no response by seller or customer"
                ],
                'code' => 200,
                'status' => true
            ];
        }catch(Exception $e){
            return [
                'message' => 'Error',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ];
        }
    }
}
