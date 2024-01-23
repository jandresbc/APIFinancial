<?php

namespace App\Http\Services;

use Illuminate\Http\Request;
use App\Http\Integrations\AwsMessenger;
use App\Models\CrLog\SelfManagerLog;
use App\Http\Integrations\Loan\Loan;


class SelfManagerService extends Service
{

    // this function authenticate the user.
    public function authenticate(Request $request)
    {
        $text = $this->randPassword();

        $sms = new AwsMessenger();
        $log = new SelfManagerLog();
        $loan = new Loan();

        $get = $loan->getClient($request->document);

        if(isset($get->Resultado->Resultado) && $get->Resultado->Resultado == 2){
            return [
                "message" => "The client does not exist.",
                "code"=>$text
            ];
        }

        //Cancelar todos los codigos anteriores.
        $clear = $log->where('document',$request->document)->get();

        foreach ($clear as $c){
            $c->status = 0;
            $c->save();
        }
        //Fin

        $phone = $get->Persona->Domicilio->TelefonoCelular;
        
        $request->phone = (int)$phone;
        $request->message = "Creditek: Tu codigo de ingreso es: ".$text;
        $request->country_code = 57;

        $sms->sendSMS($request);

        $log->code = $text;
        $log->status = 1;
        $log->document = $request->document;
        $log->country_code = 57;
        $log->phone = $phone;
        $log->save();

        return [
            "message" => "Code generated successfully",
            "code"=>$text
        ];
    }

    private function randPassword(): int
    {
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        // Output: 54esmdr0qf
        // return substr(str_shuffle($permitted_chars), 0, 6);
        mt_srand (time());
        return mt_rand(100000,999999);
    }

    public function validateAuth(Request $request)
    {
        $validate = false;
        $log = SelfManagerLog::where("document",$request->document)
        ->where("code",$request->code)
        ->where("status",1)->latest()->first();

        if($log){
            $validate = true;
            $log->status = 0;
            $log->save();
            return [
                "message" => "Valid Code",
                "validate"=>$validate,
                "code"=>$log->code
            ];
        }else{
            return [
                "message" => "Invalid Code",
                "validate"=>$validate,
                "code"=>$request->code
            ];
        }


    }

    //Get information of Clients and Credits.
    public function getInfoClient(Request $request)
    {
        $loan = new Loan();

        $client = $loan->getClient($request->document);

        if(isset($client->Persona)){
            $Persona = $client->Persona;

            $credits = $loan->getCredits($Persona->Id);
            $credito = [];
            $details = [];
            $documento = "";

            if(isset($credits->Credito) && count($credits->Credito) > 0){
                $credito = $credits->Credito;
                $details = $loan->getCreditDetails($credito[0]->IdSolicitud)->CreditoDetalles;

                $documents = $loan->getDocuments($credito[0]->IdSolicitud);

                if(isset($documents->PathArchivo) && $documents->PathArchivo != null){
                    $documento = $documents->PathArchivo;
                }
            }

            return [
                "Persona" => $Persona,
                "Credito"=> $credito,
                "Detalles"=>$details,
                "Documentos" => $documento
            ];
        }
    }
}
