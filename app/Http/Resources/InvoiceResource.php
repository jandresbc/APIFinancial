<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            "id" => $this->id,
            "hash_id" => $this->hash_id,
            "loan_id" => $this->loa_id,
            "id" => $this->id,
            "loan_id" => $this->loan_id,
            "external_id" => $this->external_id,
            "quota" => $this->quota,
            "due_date" => $this->due_date,
            "formated_due_date" => (isset($this->due_date)) ? $this->due_date->format('d-m-Y') : $this->due_date,
            "due_prefix" => $this->due_prefix,
            "number" => $this->number,
            "ERP_doc_name" => $this->ERP_doc_name,
            "ERP_doc_date" => $this->ERP_doc_date,
            "formated_ERP_doc_date" => (isset($this->ERP_doc_date)) ? $this->ERP_doc_date->format('Y-m-d') : $this->ERP_doc_date,
            "account_id" => $this->account_id,
            "identification" => $this->identification,
            "first_name" => $this->first_name,
            "last_name" => $this->last_name,
            "full_name" => $this->first_name.' '.$this->last_name,
            "total_value" => $this->total_value,
            "deleted_at" => (isset($this->deleted_at)) ? $this->deleted_at->format('Y-m-d H:i:s') : $this->deleted_at,
            "created_at" => (isset($this->created_at)) ? $this->created_at->format('Y-m-d H:i:s') : $this->created_at,
            "updated_at" => (isset($this->updated_at)) ? $this->updated_at->format('Y-m-d H:i:s') : $this->updated_at,
            "formated_deleted_at" => (isset($this->deleted_at)) ? $this->deleted_at->format('d-m-Y H:i:s') : $this->deleted_at,
            "formated_created_at" => (isset($this->created_at)) ? $this->created_at->format('d-m-Y H:i:s') : $this->created_at,
            "formated_updated_at" => (isset($this->updated_at)) ? $this->updated_at->format('d-m-Y H:i:s') : $this->updated_at,
        ];
    }
}
