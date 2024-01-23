<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagos extends Model
{
    protected $connection = 'mysql';
    use HasFactory;

    protected $fillable = [
        'fecha_hora_pago',
        'monto',
        'documento',
        'plataforma',
        'pagador',
        'prestamo_id',
        'novedad'
    ];
}
