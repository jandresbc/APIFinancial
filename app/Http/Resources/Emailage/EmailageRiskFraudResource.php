<?php

namespace App\Http\Resources\Emailage;

use Illuminate\Http\Resources\Json\JsonResource;

class EmailageRiskFraudResource extends JsonResource
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
            'email'     => $this->email,
            'flag'  => $this->flag,
            'queryType' => $this->queryType,
            'created'  => $this->created,
        ];
    }
}
