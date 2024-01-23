<?php

namespace App\Http\Resources\Emailage;

use Illuminate\Http\Resources\Json\JsonResource;

class EmailageRiskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $ipAddress = '';

        if(isset($this->results[0]->ipaddress)) {
            $ipAddress = $this->results[0]->ipaddress;
        } else {
            $ipAddress = '';
        }

        return [
            'cellphone' => $this->phone,
            'email'     => $this->results[0]->email,
            'firstName' => $this->firstname,
            'ip'        => $ipAddress,
            'lastName'  => $this->lastname,
            'riskBandId' => $this->results['0']->EARiskBandID,
            'score'   => $this->results['0']->EAScore,
        ];
    }
}
