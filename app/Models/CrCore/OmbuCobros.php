<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OmbuCobros extends Model
{
    protected $connection = 'mysql';
    use HasFactory;

    protected $fillable = [
        'prestamo_id',
        'cuota_id',
        'monto',
        'data',
        'tipo_moneda',
        'estado',
        'sys_usuario_id',
        'nro_comprobante',
        'broker_id'
    ];

    public $timestamps = false;
}
