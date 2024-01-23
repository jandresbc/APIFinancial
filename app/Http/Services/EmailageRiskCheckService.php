<?php

namespace App\Http\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Integrations\Emailage\EmailageRisk;

class EmailageRiskCheckService {
    private $emailage;

    public function __construct(EmailageRisk $emailageRiskService)
    {
        $this->emailage = $emailageRiskService;
    }

    public function checkRiskBand(int $requestId)
    {
        try {
            $request = DB::connection('mysql')->select('select * from ombu_solicitudes where id = ?', [$requestId]);

            if (count($request) < 1) {
                return response()->json([
                    'error' => true,
                    'status' => false,
                    'message' => 'The request ID is not available or does not exist'
                ]);
            }

            $loan = DB::connection('mysql')->select('select * from ombu_cc_prestamos where solicitud_id = ?', [$requestId]);

            $approvedRequest = $request[0]->estado_solicitud;

            $solicitud = json_decode($request[0]->data);
            //$solicitud = $request;
            //dd($request);
            if(empty($solicitud->email)) {
                return response()->json([
                    'error' => true,
                    'status' => false,
                    'message' => 'The request does not have an email, it cannot be processed'
                ]);
            }


            $now = Carbon::now();
            $age = Carbon::parse($solicitud->fecha_nac)->diffInYears($now);

            $data = (object) [
                "cellphone" => $solicitud->tel_movil,
                "email" => $solicitud->email,
                "firstName" => $solicitud->nombre,
                "ip" => '',
                "lastName" => $solicitud->apellido,
            ];
            $data = (object) $data;

            $emailage = $this->emailage->clientRiskValidation($data);

            $lastLog = $this->emailage->lastRequestToEmailage($data);


            if ($lastLog) {
                $emailage = json_decode($lastLog->data);
            } else {
                $emailage = $this->emailage->clientRiskValidation($data);
            }

            if (isset($emailage->original)) {
                return $emailage;
            }

            $riskId = intval($emailage->results[0]->EARiskBandID);

            $emailInexistent = intval($emailage->results[0]->EAStatusID);

            if($emailInexistent === 0 || $emailInexistent >= 3) {
                return false;
            } else if($approvedRequest === 'RECH'){
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client already has a rejected request', 'data' => $emailage]);
                }

                return true;
            } else if ($loan) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a request in progress', 'data' => $emailage]);
                }

                return false;
            } else if ($riskId <= 3) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a low risk index', 'data' => $emailage]);
                }

                return true;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->phone_status === 'Invalid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created less than 30 days ago and the telephone number is invalid', 'data' => $emailage]);
                }

                return false;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->phone_status === 'Valid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created less than 30 days ago and the telephone number is valid', 'data' => $emailage]);
                }

                return false;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->totalhits > 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created less than 30 days ago and has been verified more than once', 'data' => $emailage]);
                }

                return false;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->uniquehits > 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created less than 30 days ago and has been verified more than once by companies', 'data' => $emailage]);
                }

                return false;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->phone_status === 'Invalid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created more than 30 days ago and the phone number is not valid', 'data' => $emailage]);
                }

                return true;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->phone_status === 'Valid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created more than 30 days ago and the phone number is valid', 'data' => $emailage]);
                }

                return true;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->totalhits <= 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created more than 30 days ago and has been verified less than once', 'data' => $emailage]);
                }

                return true;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->uniquehits <= 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created more than 30 days ago and has been verified less than once', 'data' => $emailage]);
                }

                return true;
            } else if ($riskId === 3 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->phone_status === 'Invalid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created more than 30 days ago and has been verified less than once by companies', 'data' => $emailage]);
                }

                return true;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->phone_status === 'Invalid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created less than 30 days ago, and the phone number is not valid', 'data' => $emailage]);
                }

                return false;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->totalhits > 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created less than 30 days ago, and has been verified less than once', 'data' => $emailage]);
                }

                return false;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->uniquehits > 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created less than 30 days ago and has been verified less than once by companies', 'data' => $emailage]);
                }

                return false;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->phone_status === 'Invalid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created more than 30 days ago, and the phone number is not valid', 'data' => $emailage]);
                }

                return true;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->phone_status === 'Valid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created more than 30 days ago, and the phone number is valid', 'data' => $emailage]);
                }

                return true;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->totalhits <= 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created more than 30 days ago and has been verified less than once', 'data' => $emailage]);
                }

                return true;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->uniquehits <= 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created more than 30 days ago and has been verified less than once by companies', 'data' => $emailage]);
                }

                return true;
            } else if ($riskId === 3 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->phone_status === 'Invalid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data->email, 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created more than 30 days ago and has been verified less than once by companies', 'data' => $emailage]);
                }

                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return response()->json([
                'code' => $e->getCode(),
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }

    }

    public function checkRiskBandV2($data)
    {
        try {
            $now = Carbon::now();
            $age = Carbon::parse($data['dateBirth'])->diffInYears($now);
            $dataEmail = (object) [
                "cellphone" => $data['phone'],
                "email" => $data['email'],
                "firstName" => $data['name'],
                "ip" => '',
                "lastName" => $data['lastName'],
            ];

            $this->emailage->clientRiskValidation($dataEmail);

            $lastLog = $this->emailage->lastRequestToEmailage($dataEmail);


            if ($lastLog) {
                $emailage = json_decode($lastLog->data, false, 512, JSON_THROW_ON_ERROR);
            } else {
                $emailage = $this->emailage->clientRiskValidation($dataEmail);
            }

            if (isset($emailage->original)) {
                return $emailage;
            }

            $riskId = intval($emailage->results[0]->EARiskBandID);

            $emailInexistent = intval($emailage->results[0]->EAStatusID);

            if($emailInexistent === 0 || $emailInexistent >= 3) {
                return false;
            } else if ($riskId <= 3) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a low risk index', 'data' => $emailage]);
                }
                return true;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->phone_status === 'Invalid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created less than 30 days ago and the telephone number is invalid', 'data' => $emailage]);
                }
                return false;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->phone_status === 'Valid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created less than 30 days ago and the telephone number is valid', 'data' => $emailage]);
                }
                return false;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->totalhits > 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created less than 30 days ago and has been verified more than once', 'data' => $emailage]);
                }
                return false;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->uniquehits > 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created less than 30 days ago and has been verified more than once by companies', 'data' => $emailage]);
                }
                return false;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->phone_status === 'Invalid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created more than 30 days ago and the phone number is not valid', 'data' => $emailage]);
                }
                return true;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->phone_status === 'Valid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created more than 30 days ago and the phone number is valid', 'data' => $emailage]);
                }
                return true;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->totalhits <= 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created more than 30 days ago and has been verified less than once', 'data' => $emailage]);
                }
                return true;
            } else if ($riskId === 4 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->uniquehits <= 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created more than 30 days ago and has been verified less than once', 'data' => $emailage]);
                }
                return true;
            } else if ($riskId === 3 && $age >= 22 && $age <= 35 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->phone_status === 'Invalid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 22 and 35 years old, the email was created more than 30 days ago and has been verified less than once by companies', 'data' => $emailage]);
                }
                return true;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->phone_status === 'Invalid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created less than 30 days ago, and the phone number is not valid', 'data' => $emailage]);
                }
                return false;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->totalhits > 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created less than 30 days ago, and has been verified less than once', 'data' => $emailage]);
                }
                return false;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days < 30 && $emailage->results[0]->uniquehits > 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'RECH', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created less than 30 days ago and has been verified less than once by companies', 'data' => $emailage]);
                }
                return false;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->phone_status === 'Invalid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created more than 30 days ago, and the phone number is not valid', 'data' => $emailage]);
                }
                return true;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->phone_status === 'Valid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created more than 30 days ago, and the phone number is valid', 'data' => $emailage]);
                }
                return true;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->totalhits <= 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created more than 30 days ago and has been verified less than once', 'data' => $emailage]);
                }
                return true;
            } else if ($riskId === 4 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->uniquehits <= 1) {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created more than 30 days ago and has been verified less than once by companies', 'data' => $emailage]);
                }
                return true;
            } else if ($riskId === 3 && $age >= 35 && $age <= 55 && $emailage->results[0]->domain_creation_days > 30 && $emailage->results[0]->phone_status === 'Invalid') {
                if (!$lastLog) {
                    Log::channel('emailage')->info('Success client risk fraud service', ['email' => $data['email'], 'logType' => 'info', 'status' => 'APRO', 'reason' => 'The client has a moderate risk index, is between 35 and 55 years old, the email was created more than 30 days ago and has been verified less than once by companies', 'data' => $emailage]);
                }
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ], 500);
        }

    }
}
