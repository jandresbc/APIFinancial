<?php

namespace App\Http\Integrations;

use App\Http\Services\ParameterService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JetBrains\PhpStorm\ArrayShape;

class UltraMsg {
    protected string $urlApiAccount = 'https://api.ultramsg.com/instance3063/messages/chat';
    public function sendMessage($message, $phone){
        try{
            $messagePredetermined = new ParameterService();
            $TOKEN = $messagePredetermined->get(20, 'token_ultramsg');
            $client = new Client();
            $options = [
                'headers' =>[
                    "content-type" => "application/x-www-form-urlencoded"
                ],
                'form_params' => [
                    "token"=>$TOKEN,
                    "to" => $phone,
                    "body" => $message,
                    "priority" => 1,
                    "referenceId" => ""
                ]
            ];
            $res = $client->request('POST',$this->urlApiAccount, $options);
            return json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR);
        }catch(Exception $e){
            return [
                'message' => 'Warn',
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
                'code' => 500,
                'status' => false
            ];
        } catch (GuzzleException $e) {
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

    public function requestMessage($request){
        try{
            $event = json_decode($request, true, 512, JSON_THROW_ON_ERROR);
            if(isset($event)){
                $request = json_encode($event, JSON_THROW_ON_ERROR) ."\n";
            }
            return $request;
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

    #[ArrayShape(["event_type" => "string", "instanceId" => "string", "data" => "array"])]
    public function triggerMessage($phone): array
    {
        return [
            "event_type"=>"message_received",
            "instanceId"=>"2949",
            "data"=>[
                "id"=>"false_status@broadcast_3A2319D78AD5D579F097_573138304953@c.us",
                "from"=>"57".$phone."@c.us",
                "to"=>"573052783779@c.us",
                "ack"=>"",
                "type"=>"chat",
                "body"=>"init",
                "fromMe"=>false,
                "isForwarded"=>false,
                "time"=>1644944822
            ]
        ];
    }
}
