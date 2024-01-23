<?php

namespace App\Http\Integrations\RapiLoan\v1\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuotasResource extends JsonResource
{
   /**
    * Transform the resource into an array.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return array
    */
   public function toArray($request)
   {
      $message = explode("–", $this["texto1"]);
      return [
         "total_debt" => (float) $this["importe"],
         "quotas_debt" => (int) filter_var($message[2], FILTER_SANITIZE_NUMBER_INT),
         "credit_id" => (int) filter_var($message[1], FILTER_SANITIZE_NUMBER_INT),
         "bar_code" => $this["barra"],
         "id_number" => $this["id_numero"]
      ];
   }
}
