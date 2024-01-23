<?php

namespace App\Http\Controllers;

use App\Http\Integrations\UltraMsg;
use App\Http\Integrations\Wompi;
use App\Http\Services\urlShortnerServices;
use App\Models\CrCore\OmbuCcCuotas;
use Exception;
use App\Mail\EmailMethodPage;
use App\Models\CrCore\contactFlows;
use App\Models\CrCore\Ombu_cc_prestamos;
use App\Models\CrCore\Ombu_solicitudes;
use App\Models\CrCore\Pagos;
use App\Models\CrLog\PaymentReminderLog;
use App\Models\CrLog\CommandContactFlowLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\JsonResponse;
use App\Models\CrLog\UrlShortLog;

class ContactFlowsController extends Controller
{

    public function dateMessage(){
        $commnadContact = new CommandContactFlowLog();
        try{
            $totalDues = 0;
            $totalPay = 0;
            $totalCost = 0;
            $resultW = ''; $resultS = ''; $resultE = '';
            //Obtain data loan
            $loan = new Ombu_cc_prestamos();
            $responseLoan = $loan->where('solicitud_id', 1015)->get();
            foreach($responseLoan as $dataLoan){
                //Condition check that the request id exists
                if($dataLoan->solicitud_id){
                    //Obtain data requests
                    $requestDemand = new Ombu_solicitudes();
                    $responseRequest = $requestDemand->where('id', $dataLoan->solicitud_id)->get()->first();
                    $data = json_decode($responseRequest['data']);
                    //Obtain data dues
                    $dues = new OmbuCcCuotas();
                    $date_actually = Carbon::now()->format("Y-m-d");
                    $responseDuesM = $dues->where('prestamo_id', $dataLoan->id)->where('fecha_venc', '<=', $date_actually)->get();
                    //obtain total, duel cost to date
                    foreach($responseDuesM as $dataDues){
                        $totalDues += $dataDues->monto_cuota + $dataDues->monto_mora;
                    }
                    //obtain total, payemntz cost to date
                    $payementz = new Pagos();
                    $responsePay = $payementz->where('documento', $data->nro_doc)->where('fecha_hora_pago', '<=', $date_actually)->get();
                    foreach($responsePay as $dataPay){
                        $totalPay += $dataPay->monto;
                    }
                    if($totalDues > $totalPay){
                        $totalCost = $totalDues - $totalPay;
                    }else{
                        $totalCost = $totalDues;
                    }
                    $responseDuesW = $dues->where('prestamo_id',$dataLoan->id)->orderBy('fecha_venc','DESC')->take(1)->get();
                    foreach($responseDuesW as $dataResponse){
                        //Methos for obatian date
                        $date_defeated = Carbon::parse($dataResponse->fecha_venc);
                        //Compare date actually and date the dues
                        $total_date = (int)(strtotime(Carbon::now()->format('Y-m-d')) - strtotime($date_defeated->format('Y-m-d')))/ 86400;
                        $contact = new contactFlows();
                        //Search in table contact_flows, all data are equal to the date
                        $flows = $contact->where('day_execution', $total_date)->get();
                        foreach($flows as $dataFlow){
                            switch ($dataFlow->plataform){
                                case 'whatsapp';
                                    $resultW = $this->whatssapMessage($totalCost, $dataFlow, $data, $total_date);
                                    break;
                                case 'sms':
                                    $resultS =$this->getSMS($totalCost, $dataFlow, $data, $total_date);
                                    break;
                                case 'email':
                                    $resultE = $this->getSendMail($totalCost, $dataFlow, $data, $total_date);
                                    break;
                            }
                        }
                    }
                }
            }
            //save logs the contact_flows
            $commnadContact->type = 'message of whatsapp sending success';
            $commnadContact->message = json_encode(['WhatsApp' => $resultW,'sms' => $resultS,'email' => $resultE]);
            $commnadContact->code = 200;
            $commnadContact->created_at = date('Y-m-d');
            $commnadContact->updated_at = date('Y-m-d');
            $commnadContact->save();
            //return json
            return response()->json([
                'message' => 'message of whatsapp sending success',
                'data' => [
                    'WhatsApp' => $resultW,
                    'sms' => $resultS,
                    'email' => $resultE
                ],
                'status' => true,
                'code' => 200
            ], 200);
        }catch(Exception $e){
            //save logs the contact_flows
            $commnadContact->type = 'Error al procesar la información, volverlo a intentarlo';
            $commnadContact->message = $e->getMessage();
            $commnadContact->code = $e->getCode();
            $commnadContact->created_at = date('Y-m-d');
            $commnadContact->updated_at = date('Y-m-d');
            $commnadContact->save();
            //return json
            return response()->json([
                'message' => 'Error al procesar la información, volverlo a intentarlo',
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'errorFile' => $e->getFile(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }
    //function send message for whatssap
    private function whatssapMessage($totalCost, $dataFlow,$data, $total_date){
        //Obatin controller the ultraMsg for send message for whatsapp
        $messageController = new UltraMsg();
        $result = '';
        $hour = Carbon::now()->format('H');
        //condtion where verify, the hour actually is equal in the table
        if($hour == $dataFlow->hour){
            $link = $this->linkPage($data, $totalCost);
            if($total_date >= 0){
                //Obtain link wompi, for payment
                $message = $dataFlow->message.'\n\n valor total a pagar es de: $'. number_format($totalCost, 0, ',', '.');
            }else{
                $message = $dataFlow->message;
            }
            //change name var for message
            $message = str_replace('{urlPayment}',$link['new_url'],$message);
            $message = str_replace('{nombre}',$data->nombre.$data->apellido,$message);
            $message = str_replace('{documento}',$data->nro_doc,$message);
            $message = str_replace('{email}',$data->email,$message);
            $message = str_replace('{telefono}',$data->tel_movil,$message);
            $result = $messageController->sendMessage($message, '+57'.$data->tel_movil);
        }else{
            $result = 'Time out of range';
        }
        //Saved message and number in the table payment_remider_log.php in database logs
        if($result != 'Time out of range'){
            $this->PaymentRemider($data, $message, 'WhatsApp');
        }
        return $result;
    }
    //function for send email
    private function getSendMail ($totalCost, $dataFlow, $data, $total_date){
        $result = '';
        $hour = Carbon::now()->format('H');
        //condtion where verify, the hour actually is equal in the table
        if($hour == $dataFlow->hour){
            //Obtain link wompi, for payment
            $link = $this->linkPage($data, $totalCost);
            if($total_date >= 0){
                $message = $dataFlow->message.'. Valor total a pagar es de: $'. number_format($totalCost, 0, ',', '.');
            }else{
                $message = $dataFlow->message;
            }
            //changed name var in the message
            $message = str_replace('{urlPayment}',$link['new_url'],$message);
            $message = str_replace('{nombre}',$data->nombre.$data->apellido,$message);
            $message = str_replace('{documento}',$data->nro_doc,$message);
            $message = str_replace('{email}',$data->email,$message);
            $message = str_replace('{telefono}',$data->tel_movil,$message);
            //Send message for email
            $dataMail = (object) [
                'message' => $message,
                'matter' => 'Recordatorio de pago',
                'email' => $data->email
            ];
            //Upload form for email
            Mail::to($dataMail->email)->locale($dataMail->matter)->send(new EmailMethodPage($dataMail));
        }else{
            $result = 'Time out of range';
        }
        //Saved message and number in the table payment_remider_log.php in database logs
        if($result != 'Time out of range'){
            $this->PaymentRemider($data, $message, 'Email');
        }
        return $result;
    }
    //Function for send message for SMS
    public function getSMS ($totalCost, $dataFlow, $data, $total_date) {
        $result = '';
        $aws = \AWS::createClient('sns');
        $hour = Carbon::now()->format('H');
        //condtion where verify, the hour actually is equal in the table
        if($hour == $dataFlow->hour){
            $link = $this->linkPage($data, $totalCost);
            if($total_date >= 0){
                $message = $dataFlow->message.'. Valor total a pagar es de: $'. number_format($totalCost, 0, ',', '.');
            }else{
                $message = $dataFlow->message;
            }
            //change name the var for message
            $message = str_replace('{urlPayment}',$link['new_url'],$message);
            $message = str_replace('{nombre}',$data->nombre.$data->apellido,$message);
            $message = str_replace('{documento}',$data->nro_doc,$message);
            $message = str_replace('{email}',$data->email,$message);
            $message = str_replace('{telefono}',$data->tel_movil,$message);
            $result = $link;
            $aws->publish([
                'Message' => $message,
                'PhoneNumber' => '+57'.$data->tel_movil
            ]);
        }else{
            $result = 'Time out of range';
        }
        //Saved message and number in the table payment_remider_log.php in database logs
        if($result != 'Time out of range'){
            $this->PaymentRemider($data, $message, 'Email');
        }
        return $result;
    }
    //function save logs the payment reminder
    private function PaymentRemider($data, $message, $chanel){
        $paymentR = new PaymentReminderLog();
        $paymentR->phone = $data->tel_movil;
        $paymentR->email = $data->email;
        $paymentR->message = $message;
        $paymentR->channel = $chanel;
        $paymentR->created_at = date("Y-m-d H:i:s");
        $paymentR->updated_at = date("Y-m-d H:i:s");
        $paymentR->save();
    }
    //function for create link to pay the wompi
    private function linkPage($data, $totalCost){
        //Obtain link wompi, for payment
        $wompi = new Wompi();
        $payment = $wompi->getPaymentLink(
            $data->email,
            'Pagó quince nal',
            $totalCost
        );
        $linkPay = '{
            "link": "https://checkout.wompi.co/l/'.$payment['data']['id'].'"
        }';
        //Url shortener
        $url = new urlShortnerServices();
        $logCreate = new UrlShortLog();
        $response = $url->generateURL(json_decode($linkPay));
        $logCreate->type = 'success';
        $logCreate->message = 'this message is send successful';
        $logCreate->new_url = $response['new_url'];
        $logCreate->created_at = date("Y-m-d H:i:s");
        $logCreate->updated_at = date("Y-m-d H:i:s");
        //save log url short in table
        $logCreate->save();
        return $response;
    }
}
