<?php

namespace App\Http\Integrations\RapiLoan\v1\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CashinGetResource extends JsonResource
{
   /**
    * Transform the resource into an array.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return array
    */
   public function toArray($request)
   {
      $fullName = '';
      isset($this["nombre"]) ? $fullName .= $this["nombre"] : "";
      isset($this["apellido"]) ? $fullName .= " ".$this["apellido"] : "";
      $fullName = trim($fullName);

      return [

         "data" => [
            "person" => [
               "id" => rand(1000, 9999),
               "document" => $this["id_clave"],
               "phone" => 0,
               "fullName" => $fullName
            ],
            "quotas_info" => isset($this["facturas"]) ? QuotasResource::collection($this["facturas"]) : []
         ],
         "message" => $this["codigo_respuesta"] == 0 ? $message = "success" : $message = $this["msg"],
         "code" => 200,
         "status" => true,
      ];
   }
}
