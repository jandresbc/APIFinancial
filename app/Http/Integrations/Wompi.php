<?php 

namespace App\Http\Integrations;

use Exception;
use \GuzzleHttp\Client;


class Wompi {
    private $eventKey;
    private $publicKey;
    private $privateKey;
    private $urlPaymentLinks;
    private $urlTransactions;

    public function __construct() {
        $this->eventKey = env('WOMPI_EVENT_KEY');
        $this->publicKey = env('WOMPI_PUBLIC_KEY');
        $this->privateKey = env('WOMPI_PRIVATE_KEY');
        $this->urlPaymentLinks = env('WOMPI_URL_PAYMENT_LINK');
        $this->urlTransactions = env('WOMPI_URL_TRANSACTIONS');
    }

    /**
    * Function why permited payment the user
    * @param $name->name the user
    * @param $identificacion-> number idetification the user
    */
    public function getPaymentLink($name, $description, $amount) {
        try{
            $client = new Client();
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.'prv_test_Q2edN8jslwJZonrZQKyew9fGAWKuaSVU'
                ],
                'body' => json_encode([
                    "name" => $name,
                    "description"=> $description,
                    "single_use"=> false,
                    "collect_shipping"=> false,
                    "currency"=> "COP",
                    "amount_in_cents"=> intval($amount) * 100,
                    "customer_data" => [
                        "customer_references" => [
                            [
                                "label" => "Documento de identidad",
                                "is_required" => true
                            ]
                        ]
                    ]
                ]),
            ];      
            $res = $client->request('POST', 'https://sandbox.wompi.co/v1/payment_links', $options);
            return json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR);
        }catch(Exception $e){
            return response()->json([
                'message' => 'Error al procesar la información, volverlo a intentarlo',
                'errorMessage' => $e->getMessage(),
                'errorCode' => $e->getCode(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }



    public function getTrasactionWompi($transaction){
        $client = new Client();
        try{
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
            ];
            $res = $client->request('GET', $this->urlTransactions.$transaction, $options);
            return response()->json([
                "status" => 1,
                'message' => 'success',
                "data" => json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR)
            ]);
        }catch(Exception $e){
            return response()->json([
                'message' => 'Error al procesar la información, volverlo a intentarlo',
                'errorMessage' => $e->getMessage(),
                'errorCode' => $e->getCode(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }

    public function eventValidate($event) {
        $event = $event->all();

        $wompiChecksum = $event['signature']['checksum'];

        $localChecksum = '';

        $concatChecksum = $event['data']['transaction']['id'] . $event['data']['transaction']['status'] . $event['data']['transaction']['amount_in_cents'] . $event['timestamp'] . $this->eventKey;

        $localChecksum = hash("sha256", $concatChecksum);

        if($localChecksum === $wompiChecksum) {
            return true;
        } else {
            return false;
        }
    }
}