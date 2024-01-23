<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuotasHist extends Model
{
    protected $connection = 'mysql';
    use HasFactory;

    protected $table = 'cuotas_hist';
    protected $fillable = [
        'cuota_id',
        'prestamo_id',
        'estado',
        'estado_anterior',
        'monto',
        'operacion',
        'monto_pago',
        'cobro_id',
        'resto',
        'sys_fecha_alta',
        'sys_fecha_modif'
    ];
    public $timestamps = false;
}
