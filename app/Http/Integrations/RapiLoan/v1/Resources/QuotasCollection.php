<?php

namespace App\Http\Integrations\RapiLoan\v1\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class QuotasCollection extends ResourceCollection
{
    public $collects = QuotasResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->toArray();
    }
}
