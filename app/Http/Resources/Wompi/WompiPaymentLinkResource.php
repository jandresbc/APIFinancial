<?php

namespace App\Http\Resources\Wompi;

use Illuminate\Http\Resources\Json\JsonResource;

class WompiPaymentLinkResource extends JsonResource
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
            'amount' => $this->amount_in_cents,
            'description' => $this->description,
            'id' => $this->id,
            'name' => $this->name,
            'paymentLink' => $this->paymentLink,
            'sku' => $this->sku,
        ];
    }
}
