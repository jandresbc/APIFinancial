<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OmbuCcCuotas extends Model
{
    protected $connection = 'mysql';

    protected $table = 'ombu_cc_cuotas';

    use HasFactory;

    protected $fillable = [
        'estado',
        'fecha_venc',
        'monto_cuota',
        'monto_mora',
        'cobro_id',
        'total_pagado',
        'interes_cuota',
        'amortizacion',
        'capital_restante',
        
    ];

    public $timestamps = false;
}
