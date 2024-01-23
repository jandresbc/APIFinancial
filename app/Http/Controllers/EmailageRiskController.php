<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\EmailageRiskCheckService;
use App\Http\Integrations\Emailage\EmailageRisk;
use App\Http\Requests\Emailage\EmailageBandCheckRequest;
use App\Http\Requests\Emailage\EmailageRiskRequest;
use App\Http\Resources\Emailage\EmailageRiskResource;
use App\Http\Requests\Emailage\EmailageRiskFraudRequest;
use App\Http\Resources\Emailage\EmailageRiskFraudResource;

class EmailageRiskController extends Controller
{
    protected $emailageRisk;
    protected $emailageRiskCheck;
    /**
     *
     */
    public function __construct(EmailageRisk $emailageRisk, EmailageRiskCheckService $emailageRiskCheckService)
    {
        $this->emailageRisk = $emailageRisk;
        $this->emailageRiskCheck = $emailageRiskCheckService;
    }

    public function clientRiskLevel(EmailageRiskRequest $request)
    {
        $request->validated();

        $response = $this->emailageRisk->clientRiskValidation($request);

        if (isset($response->original['code']) && $response->original['code'] >= 400) {
            return response()->json($response->original);
        }

        return new EmailageRiskResource($response);
    }

    public function clientRiskFraud(EmailageRiskFraudRequest $request)
    {
        $request->validated();

        if($request->flag === 'fraud')
        {
            $request->validate([
                'fraudCodeId' => ['required', 'integer', 'max:2'],
            ]);
        }

        $response = $this->emailageRisk->clientRiskFraud($request);

        if (isset($response->original['code']) && $response->original['code'] >= 400) {
            //System log here.

            return response()->json($response->original);
        }

        //System log here.

        return new EmailageRiskFraudResource($response);
    }

    public function checkRiskBand(EmailageBandCheckRequest $request)
    {
        $request->validated();

        $response = $this->emailageRiskCheck->checkRiskBand($request->requestId);

        if(isset($response->original)){
            return $response;
        }
        /* if(!$response['status']){
            return response()->json($response, $response['code']);
        } */

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'success',
            'data' => [
                'bandApproved' => $response
            ],
        ]);
    }

    public function checkRiskBandV2(Request $request)
    {
        $data = $request->all();

        $response = $this->emailageRiskCheck->checkRiskBandV2($data);

        if(isset($response->original)){
            return $response;
        }

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'success',
            'data' => [
                'bandApproved' => $response
            ],
        ], 200);
    }
}
