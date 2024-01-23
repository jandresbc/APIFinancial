<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Mail\EmailMethodPage;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Mail;
use App\Http\Services\LoanBotService;
use App\Http\Integrations\Wompi;
use Exception;

class MessageController extends Controller
{
    /**
     * Funcion que me permite enviar mensajes, al whatssap
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postMessage(Request $request) {
        try{
            $whatsAppService = new LoanBotService();
            $wompi = new Wompi();
            /**$payment = $wompi->getPaymentLink(
                $request->name,
                $request->description,
                $request->mount
            );*/
            $responseW = $this->getWhatssap($request);
            //$responseM = $this->getSendMail($request, $responseW);
            //$responseS = $this->getSMS($request, $responseW['responseMessage']);

            /**DB::connection('mysql2')->table('wompi_log')->insert([
                'typeLog' =>  'success',
                'message' => 'this message is send successful',
                'code_id' => $payment['data']['id'],
                'reference' => 1001056214,
                'link_payment' => $responseW['link_payment'],
                'mount' => $request->mount,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ]);*/
            return response()->json([$responseW]);
        }catch(Exception $e){
            DB::connection('mysql2')->table('wompi_log')->insert([
                'typeLog' =>  'error',
                'message' => $e->getMessage(),
                'code_id' => '',
                'reference' => 0,
                'link_payment' => '',
                'mount' => 0,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            return response()->json([
                'message' => 'Error al procesar la información, volverlo a intentarlo',
                'errorMessage' => $e->getMessage(),
                'errorCode' => $e->getCode(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }
    public function getWhatssap($request): array
    {
        $whatsAppService = new LoanBotService();
        return $whatsAppService->sendMessage(
            $request->phone,
            $request->message,
            $request->filename,
            $request->phatfilename,
            $request->imagename,
            $request->phatimagename,
            ''
        );
    }

    public function getSMS ($request, $message) {
        $aws = \AWS::createClient('sns');
        try {
            $result = $aws->publish([
                'Message' => $message,
                'PhoneNumber' => '+'.$request->phone
            ]);
            return [
                'message' => 'success, sms send to number',
                'data' => [
                    'result' => $result
                ],
                'status' => true,
                'code' => 200
            ];
        } catch (AwsException $e) {
            return [
                'message' => 'An error has occurred in our system, please try again later',
                'messageError' => $e->getMessage(),
                'code' => 500,
                'status' => false
            ];
        }
    }
    public function getSendMail ($request, $message)
    {
        try {
            $dataMail = (object) [
                'message' => $message,
                'matter' => 'Recordatorio de pago',
                'email' => $request->email
            ];
            Mail::to($dataMail->email)->locale($dataMail->matter)->send(new EmailMethodPage($dataMail));
            return [
                'message' => 'mail sending success',
                'data' => [
                    'email' => $dataMail->email,
                    'matter' => $dataMail->matter,
                    'messageMail' => $dataMail->message
                ],
                'status' => true,
                'code' => 200
            ];
        } catch (AwsException|Exception $e ){
            return response()->json([
                'message' => 'An error has occurred in our system, please try again later',
                'messageError' => $e->getMessage(),
                'code' => 500,
                'status' => false
            ], 500);
        }
    }
}
