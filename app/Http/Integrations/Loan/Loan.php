<?php

namespace App\Http\Integrations\Loan;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class Loan
{

    private $url = "https://loantest-serviciosapi.creditek.com.co/";
    private $user = "admingen";
    private $passwd = "vento2010";

    //get client data
    public function getClient($document)
    {
        try {
            $query = [
                "LoginInterface" => [
                    "Login" => $this->user,
                    "Clave" => $this->passwd
                ],
                "Documento" => $document
            ];

            $client = new Client(['base_uri' => $this->url]);
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($query, JSON_THROW_ON_ERROR),
            ];
            $res = $client->request('POST', 'integracion/api/ObtenerPersona', $options);
            
            return json_decode($res->getBody());

        } catch (Exception $th) {
            dd($th);
        }
    }

    //get credit data
    public function getCredits($id_person)
    {
        try {
            $query = [
                "LoginInterface" => [
                    "Login" => $this->user,
                    "Clave" => $this->passwd
                ],
                "IdPersona" => $id_person
            ];

            $client = new Client(['base_uri' => $this->url]);
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($query, JSON_THROW_ON_ERROR),
            ];
            $res = $client->request('POST', 'integracion/api/ObtenerCreditos', $options);
            
            return json_decode($res->getBody());

        } catch (Exception $th) {
            dd($th);
        }
    }

    //get credit data
    public function getCreditDetails($id_credit)
    {
        try {
            $query = [
                "LoginInterface" => [
                    "Login" => $this->user,
                    "Clave" => $this->passwd
                ],
                "IdSolicitud" => $id_credit
            ];

            $client = new Client(['base_uri' => $this->url]);
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($query, JSON_THROW_ON_ERROR),
            ];
            $res = $client->request('POST', 'POST /api/ecommerce/ObtenerCreditoDetalles', $options);
            
            return json_decode($res->getBody());

        } catch (Exception $th) {
            dd($th);
        }
    }

    //get credit documents
    public function getDocuments($id_credit)
    {
        try {
            $query = [
                "LoginInterface" => [
                    "Login" => $this->user,
                    "Clave" => $this->passwd
                ],
                "IdSolicitud" => $id_credit,
                "GeneraBase64" => false
            ];

            $client = new Client(['base_uri' => $this->url]);
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($query, JSON_THROW_ON_ERROR),
            ];
            $res = $client->request('POST', 'api/ecommerce/ObtenerImpresionSolicitud', $options);
            
            return json_decode($res->getBody());

        } catch (Exception $th) {
            dd($th);
        }
    }
}