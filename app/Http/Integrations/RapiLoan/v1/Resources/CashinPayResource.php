<?php

namespace App\Http\Integrations\RapiLoan\v1\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CashinPayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

      return [
         "data" => [
            "person" => [
               "id" => rand(1000, 9999),
               "document" => $this["id_numero"],
            ],
            "pay_info" => [
               "id_numero" => $this["id_numero"],
               "cod_trx" => $this["id_numero"],
               "barra" => $this["barra"],
               "codigo_respuesta" => $this["codigo_respuesta"],
               "fecha_hora_operacion" => $this["fecha_hora_operacion"],
               "msg" => $this["msg"],
            ]
         ],
         "message" => $this["msg"],
         "code" => 200,
         "status" => true,
      ];
   }
}
