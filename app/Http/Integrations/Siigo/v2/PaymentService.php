<?php

namespace App\Http\Integrations\Siigo\v2;

use App\Http\Integrations\Siigo\v2\Adapters\PaymentAdapter;
use App\Http\Integrations\Siigo\v2\TokenService;
use App\Models\Siigo\PaymentType;
use Carbon\Carbon;
use Exception;

class PaymentService
{
    private $paymentAdapter;

    public function __construct(PaymentAdapter $paymentAdapter, TokenService $tokenService)
    {
        $this->paymentAdapter = $paymentAdapter;
        $this->tokenService = $tokenService;
    }

    public function getAll()
    {
        $paymentModel = new PaymentType();
        $payment = $paymentModel->all();

        // Si la tabla esta vacia o alguna esta vencida se actualizan los campos.
        if($payment->count() === 0)
        {
            $this->upsert();
        }

        foreach($payment as $pay)
        {
            if($pay->is_expired)
            {
               $this->upsert();
            }
            continue;
        }
        return $paymentModel->all();
    }

    public function upsert()
    {
        try{
            $token = $this->tokenService->get();
            $payments= $this->paymentAdapter->getAll($token['token']);
            foreach ($payments->json() as $pay)
            {
                $this->save($pay);
            }
            return PaymentType::all();

        }
        catch(Exception $exeption)
        {
            dd($exeption);
        }
    }

    public function save($pay)
    {
        return PaymentType::updateOrCreate(['id' => $pay['id']] , $pay);
    }

    public function searchByName($paymentNames = [])
    {
        $paymentNames = !empty($paymentNames) ? $paymentNames : explode(",",config('Siigo.siigo_default_payment_type'));
        $paymentTypes = PaymentType::all();
        // Si no encuentra registros los actualiza
        if($paymentTypes->count() === 0)
        {
            $this->upsert();
        }
        $paymentTypes = PaymentType::whereIn('name',$paymentNames)->get();
        if(!isset($paymentTypes))
        {
            return false;
        }
        return $paymentTypes;
    }

}
