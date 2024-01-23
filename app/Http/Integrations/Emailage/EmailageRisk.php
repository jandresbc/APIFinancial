<?php

namespace App\Http\Integrations\Emailage;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Creditek\Emailage\CreditekLogEmailage;

class EmailageRisk {

    private $clientId;
    private $clientSecret;
    private $urlAuth;
    private $urlRisk;
    private $urlFraud;
    private $authToken;

    /**
     *
     */
    public function __construct()
    {
        $this->clientId = env("EMAILAGE_CLIENT_ID", "9C06059F609A4ED28CAC38CF9B869D2F");
        $this->clientSecret = env("EMAILAGE_CLIENT_SECRET", "BE910D8E18A140B5BD22624BDEA3E48E");
        $this->urlAuth = env("EMAILAGE_URL_AUTH", "https://api.emailage.com/oauth/v2/token/");
        $this->urlFraud = env("EMAILAGE_URL_FRAUD", "https://api.emailage.com/emailagevalidator/flag/v2/?format=json");
        $this->urlRisk = env("EMAILAGE_URL_RISK", "https://api.emailage.com/EmailAgeValidator/v2/?format=json");
    }

    public function getAuthentication()
    {
        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->urlAuth) || empty($this->urlRisk) || empty($this->urlAuth)) {
            return $this->getEmailageHttpException(403);
        }

        $response = Http::acceptJson()
            ->asForm()
            ->post($this->urlAuth, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ]);

        if ($response->clientError() || $response->serverError()) {
            return $this->getEmailageHttpException($response->status());
        }

        $tokenExtract = $response->object();

        $this->authToken = $tokenExtract->access_token;
    }

    /**
     * @param object $data
     * @return object $response
     */
    public function clientRiskValidation(object $data): object
    {
        $checkAuth = $this->getAuthentication();

        if(isset($checkAuth->original)){
            Log::channel('emailage')->error('Failed emailage authentication', ['email' => $data->email, 'logType' => 'error', 'status' => 'RECH', 'reason' => 'Failed emailage authentication', 'data' => $checkAuth->original]);

            return $checkAuth;
        }

        $requestData = [
            'email' => $data->email,
            'phone' => $data->cellphone,
            'firstname' => $data->firstName,
            'lastname' => $data->lastName,
            'ip' => $data->ip ?: ''
        ];

        $response = Http::withToken($this->authToken)
        ->acceptJson()
        ->asForm()
        ->post($this->urlRisk, $requestData);

        if ($response->clientError() || $response->serverError()) {
            $exception = $this->getEmailageHttpException($response->status());

            return $exception;
        }

        $response = $response->object();

        return $response->query;
    }

    public function clientRiskFraud(object $data)
    {
        $checkAuth = $this->getAuthentication();
        if (isset($checkAuth->original)) {
            Log::channel('emailage')->error('Failed emailage authentication', ['email' => $data->email, 'logType' => 'error', 'status' => 'RECH', 'reason' => 'Failed emailage authentication', 'data' => $checkAuth->original]);

            return $checkAuth;
        }

        $lastLog = $this->lastRequestToEmailage($data);

        if ($lastLog) {
            return json_decode($lastLog->data->data);
        }

        $requestData = [
            'query' => $data->email,
            'flag' => $data->flag,
            'fraudcodeID' => $data->fraudCodeId,
        ];

        $response = Http::withToken($this->authToken)
        ->acceptJson()
        ->asForm()
        ->post($this->urlFraud, $requestData);

        $getOAuthError = $response->object();

        if ($response->clientError() || $response->serverError() || isset($getOAuthError->oAuthStatus->errorCode)) {
            if($getOAuthError->oAuthStatus->errorCode) {
                $exception = $this->getEmailageHttpException($getOAuthError->oAuthStatus->errorCode);

                return $exception;
            }

            return $this->getEmailageHttpException($response->status());
        }

        $response = $response->object();

        return $response->query;
    }

    public function lastRequestToEmailage($data)
    {
        $lastLog = CreditekLogEmailage::where(['email' => $data->email, 'type_log' => 'info'])->orderByDesc('id')->with('data')->first();

        $numberDaysDue = DB::connection('mysql')->select('select * from config_parametros where nombre = ?', ['emailage_number_days_due']);

        if (!$numberDaysDue) {
            $numberDaysDue = DB::connection('mysql')->table('config_parametros')->insert([
                'grupo_id' => '21',
                'nombre' => 'emailage_number_days_due',
                'valor' => 5,
                'tipo' => 'INT',
                'permiso' => 'ADMIN',
                'descripcion' => 'Cantidad de dias validar cuando se realizo la ultima consulta a Emailage'
            ]);

            $numberDaysDue = [
                [
                    'valor' => 5
                ]
            ];
        }

        if ($lastLog && $lastLog->data) {
            $due = Carbon::parse($lastLog->data->consultation_date)->diffInDays(Carbon::now());

            $numberDaysDue = intval($numberDaysDue[0]->valor);

            if ($due < $numberDaysDue) {
                return $lastLog->data;
            }
        }

        return false;
    }

    private function getEmailageHttpException($code)
    {
        switch ($code) {
            case 400:
                return response()->json([
                    'code' => $code,
                    'status' => false,
                    'message' => 'Emailage: Invalid input parameter. Error message should indicate which one',
                ], $code);
                break;
            case 401:
                return response()->json([
                    'code' => $code,
                    'status' => false,
                    'message' => 'Emailage: Invalid or expired token. This can happen if an access token was either revoked or has expired. This can be fixed by re-authenticating the user',
                ], $code);
                break;
            case 403:
                return response()->json([
                    'code' => $code,
                    'status' => false,
                    'message' => 'Emailage: Invalid oAuth request (wrong consumer key, bad nonce, expired timestamp',
                ], $code);
                break;
            case 404:
                return response()->json([
                    'code' => $code,
                    'status' => false,
                    'message' => 'Emailage: File or folder was not found at the specified path',
                ], $code);
                break;
            case 405:
                return response()->json([
                    'code' => $code,
                    'status' => false,
                    'message' => 'Emailage: Request method not expected (generally should be GET or POST)',
                ], $code);
                break;
            case 503:
                return response()->json([
                    'code' => $code,
                    'status' => false,
                    'message' => 'Emailage: Calls are exceeding the defined throttle limit',
                ], $code);
                break;
            case 3001:
                return response()->json([
                    'code' => $code,
                    'status' => false,
                    'message' => 'Emailage: the signature does not match or the user/consumer key file was not found',
                ], 400);
                break;
            default:
                return response()->json([
                    'code' => $code,
                    'status' => false,
                    'message' => 'Emailage: An error occurred connecting to Emailage, please check the error at: https://helpcenter.emailage.com/hc/en-us/articles/360030596972-EmailRisk-API-Reference or or contact the administrator',
                ], 500);
            break;
        }
    }
}
