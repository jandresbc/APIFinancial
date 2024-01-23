<?php

namespace App\Http\Resources\Wompi;

use Illuminate\Http\Resources\Json\JsonResource;

class WompiTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            //Necesitaría la fecha del pago. El id de ese pago. Medio de pago si lo tiene y el estado actual.
            'id' => $this->id,
            'createdAt' => $this->created_at,
            'paymentMethodType' => $this->payment_method_type,
            'status' => $this->status,
        ];
    }
}
