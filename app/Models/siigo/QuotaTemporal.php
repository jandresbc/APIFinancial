<?php

namespace App\Models\Siigo;

use Illuminate\Database\Eloquent\Model;

class QuotaTemporal extends Model
{
    protected $table = 'ombu_cc_cuotas_facturacion';

    protected $primaryKey = 'id';

    protected $dates = ['fecha_venc'];

    public $timestamps = false;

    protected $fillable = ['facturado'];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'prestamo_id', 'id');
    }

    public function quota_record()
    {
        return $this->hasMany(QuotaRecord::class, 'cuota_id', 'id')->orderBY('id', 'desc');
    }

    public function quota_record_last()
    {
        return $this->hasOne(QuotaRecord::class, 'cuota_id', 'id')->orderBY('id', 'desc');
    }

    public function billings()
    {
        return $this->belongsTo(Billing::class, 'cuota_id', 'id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'external_id', 'id');
    }
}
