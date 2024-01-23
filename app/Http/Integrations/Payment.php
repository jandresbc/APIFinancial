<?php

namespace App\Http\Integrations;

use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Payment{

    protected $API_LOGIN_DEV = "DV-CREDITEK-CO-SERVER";
    protected $API_KEY_DEV = "X1xVypVNbYeszQOqsmlb0eVXK8oNQA";
    protected $urlApiAccount = 'https://noccapi-stg.paymentez.com/linktopay/init_order/';

    public function conectionPaymantez($dataUser){
        try{
            $server_application_code = $this->API_LOGIN_DEV;
            $server_app_key = $this->API_KEY_DEV ;
    
            $date = new DateTime();
            $unix_timestamp = $date->getTimestamp();
    
            $uniq_token_string = $server_app_key.$unix_timestamp;
            $uniq_token_hash = hash('sha256', $uniq_token_string);
            $auth_token = base64_encode($server_application_code.";".$unix_timestamp.";".$uniq_token_hash);
            
            $response = $this->PaymentLink($auth_token, $dataUser);
            return [
                'message' => 'success',
                'data' => [
                    '\nUNIQTOKENST' => $uniq_token_string,
                    '\nUNIQTOHAS' => $uniq_token_hash,
                    '\nAUTHTOKEN' => $auth_token
                ],
                'Link' => $response,
                'code' => 200,
                'status' => true
            ];
        }catch(Exception $e){
            return [
                'message' => 'Ha ocurrido un error en el sistema al generar el link, por favor intente más tarde',
                'errorMessage' => $e->getMessage(),
                'errorCode' => $e->getCode(),
                'code' => 500,
                'status' => false
            ];
            
        }
    }

    private function PaymentLink($auth_token, $dataUser){
        $client = new Client();
        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Auth-Token' =>  $auth_token
                
            ],
            'body' => json_encode([
                'user' => [
                    'id' => $dataUser->userId,
                    'email' => $dataUser->userEmail,
                    'name' => $dataUser->userName,
                    'last_name' => $dataUser->userLast_name,
                ],
                'order' => [
                    'dev_reference' => $dataUser->orderDev_reference,
                    'description' => $dataUser->orderDescription,
                    'amount' => $dataUser->orderAmount,
                    'installments_type' => 0,
                    'vat' => 0.19,
                    'currency' => 'COP',
                ],
                'configuration' => [
                    'artial_payment' => false,
                    'xpiration_days' => 3,
                    'llowed_payment_methods' => 'All',
                    'success_url' => 'https://paymentez.github.io/api-doc/es/?php#autenticacion',
                    'failure_url' => 'https://paymentez.github.io/api-doc/es/?php#autenticacion',
                    'pending_url' => 'https://paymentez.github.io/api-doc/es/?php#autenticacion',
                    'review_url' => 'https://paymentez.github.io/api-doc/es/?php#autenticacion'
                ]
            ])
        ];
        $res = $client->request('POST',$this->urlApiAccount, $options);

        return json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}